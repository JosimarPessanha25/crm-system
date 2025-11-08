<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Activity;
use App\Models\User;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Opportunity;
use Illuminate\Database\Eloquent\Collection;
use Psr\Log\LoggerInterface;
use DateTime;

/**
 * ActivityService - Business logic for activity and task management
 * 
 * Handles all activity-related business operations including scheduling,
 * calendar management, task completion tracking, and reminder systems.
 */
class ActivityService
{
    private LoggerInterface $logger;

    // Valid activity types
    private const ACTIVITY_TYPES = [
        'ligacao',
        'email',
        'reuniao',
        'tarefa',
        'follow_up',
        'proposta',
        'apresentacao'
    ];

    // Valid statuses
    private const VALID_STATUSES = [
        'agendada',
        'em_andamento',
        'concluida',
        'cancelada',
        'adiada'
    ];

    // Valid priorities
    private const VALID_PRIORITIES = [
        'baixa',
        'media',
        'alta',
        'urgente'
    ];

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * List activities with pagination and filtering
     */
    public function list(int $page = 1, int $limit = 20, array $filters = []): array
    {
        $query = Activity::with(['responsavel', 'company', 'contact', 'opportunity']);

        // Apply search filter
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('titulo', 'like', $search)
                  ->orWhere('descricao', 'like', $search);
            });
        }

        // Apply status filter
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Apply type filter
        if (!empty($filters['type'])) {
            $query->where('tipo', $filters['type']);
        }

        // Apply priority filter
        if (!empty($filters['priority'])) {
            $query->where('prioridade', $filters['priority']);
        }

        // Apply responsible user filter
        if (!empty($filters['user_id'])) {
            $query->where('usuario_responsavel_id', $filters['user_id']);
        }

        // Apply company filter
        if (!empty($filters['company_id'])) {
            $query->where('empresa_id', $filters['company_id']);
        }

        // Apply date range filter
        if (!empty($filters['start_date'])) {
            $query->where('data_vencimento', '>=', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $query->where('data_vencimento', '<=', $filters['end_date']);
        }

        // Apply overdue filter
        if (!empty($filters['overdue']) && $filters['overdue']) {
            $query->where('data_vencimento', '<', now())
                  ->whereNotIn('status', ['concluida', 'cancelada']);
        }

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'data_vencimento';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $query->orderBy($sortBy, $sortOrder);

        // Get total count for pagination
        $total = $query->count();

        // Apply pagination
        $offset = ($page - 1) * $limit;
        $activities = $query->offset($offset)->limit($limit)->get();

        // Transform data
        $data = $activities->map(function ($activity) {
            return $this->transformActivityData($activity);
        });

        return [
            'data' => $data,
            'current_page' => $page,
            'per_page' => $limit,
            'total' => $total,
            'total_pages' => ceil($total / $limit)
        ];
    }

    /**
     * Find activity by ID
     */
    public function findById(int $id): ?array
    {
        $activity = Activity::with(['responsavel', 'company', 'contact', 'opportunity'])->find($id);
        
        if (!$activity) {
            return null;
        }

        return $this->transformActivityData($activity, true);
    }

    /**
     * Create a new activity
     */
    public function create(array $data): array
    {
        $this->validateActivityData($data);
        
        // Validate relationships
        if (!empty($data['usuario_responsavel_id'])) {
            if (!User::find($data['usuario_responsavel_id'])) {
                throw new \InvalidArgumentException('Responsible user not found');
            }
        }

        if (!empty($data['empresa_id'])) {
            if (!Company::find($data['empresa_id'])) {
                throw new \InvalidArgumentException('Company not found');
            }
        }

        if (!empty($data['contato_id'])) {
            if (!Contact::find($data['contato_id'])) {
                throw new \InvalidArgumentException('Contact not found');
            }
        }

        if (!empty($data['oportunidade_id'])) {
            if (!Opportunity::find($data['oportunidade_id'])) {
                throw new \InvalidArgumentException('Opportunity not found');
            }
        }

        // Set defaults
        $data['status'] = $data['status'] ?? 'agendada';
        $data['prioridade'] = $data['prioridade'] ?? 'media';

        $activity = Activity::create($data);

        $this->logger->info('Activity created', [
            'activity_id' => $activity->id,
            'titulo' => $activity->titulo,
            'tipo' => $activity->tipo,
            'data_vencimento' => $activity->data_vencimento,
            'responsavel_id' => $activity->usuario_responsavel_id
        ]);

        return $this->transformActivityData($activity);
    }

    /**
     * Update an existing activity
     */
    public function update(int $id, array $data): ?array
    {
        $activity = Activity::find($id);
        
        if (!$activity) {
            return null;
        }

        $this->validateActivityData($data, $id);

        // Validate relationships if being changed
        if (!empty($data['usuario_responsavel_id'])) {
            if (!User::find($data['usuario_responsavel_id'])) {
                throw new \InvalidArgumentException('Responsible user not found');
            }
        }

        if (!empty($data['empresa_id'])) {
            if (!Company::find($data['empresa_id'])) {
                throw new \InvalidArgumentException('Company not found');
            }
        }

        if (!empty($data['contato_id'])) {
            if (!Contact::find($data['contato_id'])) {
                throw new \InvalidArgumentException('Contact not found');
            }
        }

        if (!empty($data['oportunidade_id'])) {
            if (!Opportunity::find($data['oportunidade_id'])) {
                throw new \InvalidArgumentException('Opportunity not found');
            }
        }

        // Track status changes
        $oldStatus = $activity->status;
        $newStatus = $data['status'] ?? $oldStatus;

        // Set completion date if marking as completed
        if ($newStatus === 'concluida' && $oldStatus !== 'concluida') {
            $data['data_conclusao'] = now();
        }

        $activity->update($data);

        // Log status changes
        if ($oldStatus !== $newStatus) {
            $this->logger->info('Activity status changed', [
                'activity_id' => $activity->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus
            ]);
        }

        $this->logger->info('Activity updated', [
            'activity_id' => $activity->id,
            'changes' => array_keys($data)
        ]);

        return $this->transformActivityData($activity->fresh());
    }

    /**
     * Delete an activity (soft delete)
     */
    public function delete(int $id): bool
    {
        $activity = Activity::find($id);
        
        if (!$activity) {
            return false;
        }

        // Check if activity can be deleted
        if ($activity->status === 'concluida') {
            throw new \InvalidArgumentException('Cannot delete completed activities');
        }

        $activity->delete();

        $this->logger->info('Activity deleted', [
            'activity_id' => $id,
            'titulo' => $activity->titulo
        ]);

        return true;
    }

    /**
     * Get calendar view of activities
     */
    public function getCalendar(string $startDate, string $endDate, ?int $userId = null): array
    {
        $query = Activity::with(['responsavel', 'company', 'contact'])
            ->whereBetween('data_vencimento', [$startDate, $endDate])
            ->whereNotIn('status', ['cancelada']);

        if ($userId) {
            $query->where('usuario_responsavel_id', $userId);
        }

        $activities = $query->orderBy('data_vencimento')->get();

        // Group by date
        $calendar = [];
        foreach ($activities as $activity) {
            $date = $activity->data_vencimento->format('Y-m-d');
            
            if (!isset($calendar[$date])) {
                $calendar[$date] = [];
            }

            $calendar[$date][] = $this->transformActivityData($activity);
        }

        return $calendar;
    }

    /**
     * Get upcoming activities
     */
    public function getUpcoming(int $days = 7, ?int $userId = null): Collection
    {
        $query = Activity::with(['responsavel', 'company', 'contact'])
            ->where('data_vencimento', '>=', now())
            ->where('data_vencimento', '<=', now()->addDays($days))
            ->whereNotIn('status', ['concluida', 'cancelada']);

        if ($userId) {
            $query->where('usuario_responsavel_id', $userId);
        }

        return $query->orderBy('data_vencimento')->get();
    }

    /**
     * Get overdue activities
     */
    public function getOverdue(?int $userId = null): Collection
    {
        $query = Activity::with(['responsavel', 'company', 'contact'])
            ->where('data_vencimento', '<', now())
            ->whereNotIn('status', ['concluida', 'cancelada']);

        if ($userId) {
            $query->where('usuario_responsavel_id', $userId);
        }

        return $query->orderBy('data_vencimento')->get();
    }

    /**
     * Complete an activity
     */
    public function complete(int $id, ?string $notes = null): ?array
    {
        $activity = Activity::find($id);
        
        if (!$activity) {
            return null;
        }

        if ($activity->status === 'concluida') {
            throw new \InvalidArgumentException('Activity is already completed');
        }

        if ($activity->status === 'cancelada') {
            throw new \InvalidArgumentException('Cannot complete cancelled activity');
        }

        $updateData = [
            'status' => 'concluida',
            'data_conclusao' => now(),
            'resultado' => $notes
        ];

        $activity->update($updateData);

        $this->logger->info('Activity completed', [
            'activity_id' => $id,
            'completion_date' => now(),
            'notes' => $notes
        ]);

        return $this->transformActivityData($activity);
    }

    /**
     * Reschedule an activity
     */
    public function reschedule(int $id, string $newDateTime, ?string $reason = null): ?array
    {
        $activity = Activity::find($id);
        
        if (!$activity) {
            return null;
        }

        if (in_array($activity->status, ['concluida', 'cancelada'])) {
            throw new \InvalidArgumentException('Cannot reschedule completed or cancelled activity');
        }

        try {
            $newDate = new DateTime($newDateTime);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Invalid date format');
        }

        $oldDate = $activity->data_vencimento;

        $activity->update([
            'data_vencimento' => $newDate,
            'status' => 'adiada',
            'observacoes' => $reason ? 
                $activity->observacoes . "\nRescheduled: " . $reason : 
                $activity->observacoes
        ]);

        $this->logger->info('Activity rescheduled', [
            'activity_id' => $id,
            'old_date' => $oldDate,
            'new_date' => $newDate,
            'reason' => $reason
        ]);

        return $this->transformActivityData($activity);
    }

    /**
     * Get activity statistics
     */
    public function getStats(?int $userId = null): array
    {
        $query = Activity::query();
        
        if ($userId) {
            $query->where('usuario_responsavel_id', $userId);
        }

        // Basic counts
        $totalActivities = $query->count();
        $completedActivities = $query->where('status', 'concluida')->count();
        $scheduledActivities = $query->where('status', 'agendada')->count();
        $overdueActivities = $query->where('data_vencimento', '<', now())
            ->whereNotIn('status', ['concluida', 'cancelada'])
            ->count();

        // Today's activities
        $todayActivities = $query->whereDate('data_vencimento', today())
            ->whereNotIn('status', ['concluida', 'cancelada'])
            ->count();

        // This week's activities
        $weekActivities = $query->whereBetween('data_vencimento', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ])
            ->whereNotIn('status', ['concluida', 'cancelada'])
            ->count();

        // Completion rate
        $completionRate = $totalActivities > 0 ? 
            round(($completedActivities / $totalActivities) * 100, 2) : 0;

        // Activity type distribution
        $typeDistribution = $query->selectRaw('tipo, COUNT(*) as count')
            ->groupBy('tipo')
            ->get()
            ->keyBy('tipo');

        // Priority distribution
        $priorityDistribution = $query->selectRaw('prioridade, COUNT(*) as count')
            ->whereNotIn('status', ['concluida', 'cancelada'])
            ->groupBy('prioridade')
            ->get()
            ->keyBy('prioridade');

        return [
            'overview' => [
                'total_activities' => $totalActivities,
                'completed_activities' => $completedActivities,
                'scheduled_activities' => $scheduledActivities,
                'overdue_activities' => $overdueActivities,
                'today_activities' => $todayActivities,
                'week_activities' => $weekActivities,
                'completion_rate' => $completionRate
            ],
            'type_distribution' => $typeDistribution,
            'priority_distribution' => $priorityDistribution
        ];
    }

    /**
     * Validate activity input data
     */
    private function validateActivityData(array $data, ?int $activityId = null): void
    {
        // Required fields for creation
        if ($activityId === null) {
            if (empty($data['titulo'])) {
                throw new \InvalidArgumentException('Activity title is required');
            }
            if (empty($data['tipo'])) {
                throw new \InvalidArgumentException('Activity type is required');
            }
        }

        // Type validation
        if (!empty($data['tipo']) && !in_array($data['tipo'], self::ACTIVITY_TYPES)) {
            throw new \InvalidArgumentException('Invalid activity type specified');
        }

        // Status validation
        if (!empty($data['status']) && !in_array($data['status'], self::VALID_STATUSES)) {
            throw new \InvalidArgumentException('Invalid status specified');
        }

        // Priority validation
        if (!empty($data['prioridade']) && !in_array($data['prioridade'], self::VALID_PRIORITIES)) {
            throw new \InvalidArgumentException('Invalid priority specified');
        }

        // Date validation
        if (!empty($data['data_vencimento'])) {
            try {
                new DateTime($data['data_vencimento']);
            } catch (\Exception $e) {
                throw new \InvalidArgumentException('Invalid due date format');
            }
        }

        // Duration validation
        if (!empty($data['duracao'])) {
            if (!is_numeric($data['duracao']) || $data['duracao'] <= 0) {
                throw new \InvalidArgumentException('Duration must be a positive number');
            }
        }

        // Title length validation
        if (!empty($data['titulo']) && strlen($data['titulo']) > 255) {
            throw new \InvalidArgumentException('Activity title is too long');
        }
    }

    /**
     * Transform activity data for API response
     */
    private function transformActivityData(Activity $activity, bool $includeDetails = false): array
    {
        $data = [
            'id' => $activity->id,
            'titulo' => $activity->titulo,
            'descricao' => $activity->descricao,
            'tipo' => $activity->tipo,
            'status' => $activity->status,
            'prioridade' => $activity->prioridade,
            'data_vencimento' => $activity->data_vencimento?->format('Y-m-d H:i:s'),
            'data_conclusao' => $activity->data_conclusao?->format('Y-m-d H:i:s'),
            'duracao' => $activity->duracao,
            'resultado' => $activity->resultado,
            'observacoes' => $activity->observacoes,
            'usuario_responsavel_id' => $activity->usuario_responsavel_id,
            'empresa_id' => $activity->empresa_id,
            'contato_id' => $activity->contato_id,
            'oportunidade_id' => $activity->oportunidade_id,
            'responsavel' => $activity->responsavel ? [
                'id' => $activity->responsavel->id,
                'nome' => $activity->responsavel->nome
            ] : null,
            'company' => $activity->company ? [
                'id' => $activity->company->id,
                'nome' => $activity->company->nome
            ] : null,
            'contact' => $activity->contact ? [
                'id' => $activity->contact->id,
                'nome' => $activity->contact->nome
            ] : null,
            'opportunity' => $activity->opportunity ? [
                'id' => $activity->opportunity->id,
                'titulo' => $activity->opportunity->titulo
            ] : null,
            'created_at' => $activity->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $activity->updated_at->format('Y-m-d H:i:s')
        ];

        if ($includeDetails) {
            $data['is_overdue'] = $activity->data_vencimento && 
                $activity->data_vencimento < now() && 
                !in_array($activity->status, ['concluida', 'cancelada']);
                
            $data['days_until_due'] = $activity->data_vencimento ? 
                $activity->data_vencimento->diffInDays(now(), false) : null;
        }

        return $data;
    }

    /**
     * Search activities by title or description
     */
    public function search(string $query, int $limit = 10): Collection
    {
        $searchTerm = '%' . $query . '%';
        
        return Activity::with(['responsavel', 'company', 'contact'])
            ->where(function ($q) use ($searchTerm) {
                $q->where('titulo', 'like', $searchTerm)
                  ->orWhere('descricao', 'like', $searchTerm);
            })
            ->limit($limit)
            ->get();
    }

    /**
     * Get activities by type
     */
    public function getActivitiesByType(string $type): Collection
    {
        return Activity::with(['responsavel', 'company', 'contact'])
            ->where('tipo', $type)
            ->orderBy('data_vencimento')
            ->get();
    }
}