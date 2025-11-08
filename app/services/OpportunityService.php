<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Opportunity;
use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Psr\Log\LoggerInterface;

/**
 * OpportunityService - Business logic for sales opportunity management
 * 
 * Handles all opportunity-related business operations including pipeline management,
 * stage transitions, value calculations, and sales process automation.
 */
class OpportunityService
{
    private LoggerInterface $logger;

    // Pipeline stages in order
    private const PIPELINE_STAGES = [
        'prospeccao',
        'qualificacao',
        'proposta',
        'negociacao',
        'fechamento'
    ];

    // Valid statuses
    private const VALID_STATUSES = [
        'ativa',
        'pausada',
        'fechada_ganha',
        'fechada_perdida'
    ];

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * List opportunities with pagination and filtering
     */
    public function list(int $page = 1, int $limit = 20, array $filters = []): array
    {
        $query = Opportunity::with(['company', 'contact', 'responsavel']);

        // Apply search filter
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('titulo', 'like', $search)
                  ->orWhere('descricao', 'like', $search)
                  ->orWhereHas('company', function ($companyQuery) use ($search) {
                      $companyQuery->where('nome', 'like', $search);
                  });
            });
        }

        // Apply status filter
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Apply stage filter
        if (!empty($filters['stage'])) {
            $query->where('estagio', $filters['stage']);
        }

        // Apply company filter
        if (!empty($filters['company_id'])) {
            $query->where('empresa_id', $filters['company_id']);
        }

        // Apply responsible user filter
        if (!empty($filters['user_id'])) {
            $query->where('usuario_responsavel_id', $filters['user_id']);
        }

        // Apply value range filter
        if (!empty($filters['min_value'])) {
            $query->where('valor', '>=', $filters['min_value']);
        }
        if (!empty($filters['max_value'])) {
            $query->where('valor', '<=', $filters['max_value']);
        }

        // Apply date range filter
        if (!empty($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date']);
        }

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        // Get total count for pagination
        $total = $query->count();

        // Apply pagination
        $offset = ($page - 1) * $limit;
        $opportunities = $query->offset($offset)->limit($limit)->get();

        // Transform data
        $data = $opportunities->map(function ($opportunity) {
            return $this->transformOpportunityData($opportunity);
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
     * Find opportunity by ID
     */
    public function findById(int $id): ?array
    {
        $opportunity = Opportunity::with(['company', 'contact', 'responsavel', 'activities'])->find($id);
        
        if (!$opportunity) {
            return null;
        }

        return $this->transformOpportunityData($opportunity, true);
    }

    /**
     * Create a new opportunity
     */
    public function create(array $data): array
    {
        $this->validateOpportunityData($data);
        
        // Validate relationships
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

        if (!empty($data['usuario_responsavel_id'])) {
            if (!User::find($data['usuario_responsavel_id'])) {
                throw new \InvalidArgumentException('Responsible user not found');
            }
        }

        // Set defaults
        $data['estagio'] = $data['estagio'] ?? 'prospeccao';
        $data['status'] = $data['status'] ?? 'ativa';
        $data['probabilidade'] = $data['probabilidade'] ?? $this->getDefaultProbability($data['estagio']);

        $opportunity = Opportunity::create($data);

        $this->logger->info('Opportunity created', [
            'opportunity_id' => $opportunity->id,
            'titulo' => $opportunity->titulo,
            'valor' => $opportunity->valor,
            'empresa_id' => $opportunity->empresa_id,
            'responsavel_id' => $opportunity->usuario_responsavel_id
        ]);

        return $this->transformOpportunityData($opportunity);
    }

    /**
     * Update an existing opportunity
     */
    public function update(int $id, array $data): ?array
    {
        $opportunity = Opportunity::find($id);
        
        if (!$opportunity) {
            return null;
        }

        $this->validateOpportunityData($data, $id);

        // Validate relationships if being changed
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

        if (!empty($data['usuario_responsavel_id'])) {
            if (!User::find($data['usuario_responsavel_id'])) {
                throw new \InvalidArgumentException('Responsible user not found');
            }
        }

        // Track stage changes
        $oldStage = $opportunity->estagio;
        $newStage = $data['estagio'] ?? $oldStage;

        $opportunity->update($data);

        // Log stage changes
        if ($oldStage !== $newStage) {
            $this->logger->info('Opportunity stage changed', [
                'opportunity_id' => $opportunity->id,
                'old_stage' => $oldStage,
                'new_stage' => $newStage,
                'changed_by' => $data['usuario_responsavel_id'] ?? null
            ]);
        }

        $this->logger->info('Opportunity updated', [
            'opportunity_id' => $opportunity->id,
            'changes' => array_keys($data)
        ]);

        return $this->transformOpportunityData($opportunity->fresh());
    }

    /**
     * Delete an opportunity (soft delete)
     */
    public function delete(int $id): bool
    {
        $opportunity = Opportunity::find($id);
        
        if (!$opportunity) {
            return false;
        }

        // Check if opportunity can be deleted
        if (in_array($opportunity->status, ['fechada_ganha', 'fechada_perdida'])) {
            throw new \InvalidArgumentException('Cannot delete closed opportunities');
        }

        $opportunity->delete();

        $this->logger->info('Opportunity deleted', [
            'opportunity_id' => $id,
            'titulo' => $opportunity->titulo
        ]);

        return true;
    }

    /**
     * Get pipeline view with opportunities grouped by stage
     */
    public function getPipeline(array $filters = []): array
    {
        $query = Opportunity::with(['company', 'responsavel'])
            ->where('status', 'ativa');

        // Apply filters
        if (!empty($filters['user_id'])) {
            $query->where('usuario_responsavel_id', $filters['user_id']);
        }

        if (!empty($filters['company_id'])) {
            $query->where('empresa_id', $filters['company_id']);
        }

        $opportunities = $query->get();

        // Group by stage
        $pipeline = [];
        foreach (self::PIPELINE_STAGES as $stage) {
            $stageOpportunities = $opportunities->where('estagio', $stage);
            
            $pipeline[$stage] = [
                'stage' => $stage,
                'count' => $stageOpportunities->count(),
                'total_value' => $stageOpportunities->sum('valor'),
                'avg_probability' => $stageOpportunities->avg('probabilidade') ?? 0,
                'opportunities' => $stageOpportunities->map(function ($opp) {
                    return $this->transformOpportunityData($opp);
                })->values()
            ];
        }

        // Calculate pipeline totals
        $totals = [
            'total_opportunities' => $opportunities->count(),
            'total_value' => $opportunities->sum('valor'),
            'weighted_value' => $opportunities->sum(function ($opp) {
                return $opp->valor * ($opp->probabilidade / 100);
            })
        ];

        return [
            'pipeline' => $pipeline,
            'totals' => $totals
        ];
    }

    /**
     * Move opportunity to next/previous stage
     */
    public function moveStage(int $id, string $newStage, ?string $notes = null): ?array
    {
        $opportunity = Opportunity::find($id);
        
        if (!$opportunity) {
            return null;
        }

        if (!in_array($newStage, self::PIPELINE_STAGES)) {
            throw new \InvalidArgumentException('Invalid stage specified');
        }

        if ($opportunity->status !== 'ativa') {
            throw new \InvalidArgumentException('Cannot move stage of inactive opportunity');
        }

        $oldStage = $opportunity->estagio;
        $newProbability = $this->getDefaultProbability($newStage);

        $opportunity->update([
            'estagio' => $newStage,
            'probabilidade' => $newProbability,
            'observacoes' => $notes ? $opportunity->observacoes . "\n" . $notes : $opportunity->observacoes
        ]);

        $this->logger->info('Opportunity stage moved', [
            'opportunity_id' => $id,
            'old_stage' => $oldStage,
            'new_stage' => $newStage,
            'new_probability' => $newProbability,
            'notes' => $notes
        ]);

        return $this->transformOpportunityData($opportunity);
    }

    /**
     * Close opportunity (won or lost)
     */
    public function close(int $id, bool $won, ?string $notes = null, ?float $finalValue = null): ?array
    {
        $opportunity = Opportunity::find($id);
        
        if (!$opportunity) {
            return null;
        }

        if ($opportunity->status !== 'ativa') {
            throw new \InvalidArgumentException('Opportunity is already closed');
        }

        $status = $won ? 'fechada_ganha' : 'fechada_perdida';
        $updateData = [
            'status' => $status,
            'data_fechamento' => now(),
            'observacoes' => $notes ? $opportunity->observacoes . "\n" . $notes : $opportunity->observacoes
        ];

        if ($finalValue !== null) {
            $updateData['valor'] = $finalValue;
        }

        if ($won) {
            $updateData['probabilidade'] = 100;
            $updateData['estagio'] = 'fechamento';
        } else {
            $updateData['probabilidade'] = 0;
        }

        $opportunity->update($updateData);

        $this->logger->info('Opportunity closed', [
            'opportunity_id' => $id,
            'won' => $won,
            'final_value' => $finalValue ?? $opportunity->valor,
            'notes' => $notes
        ]);

        return $this->transformOpportunityData($opportunity);
    }

    /**
     * Get opportunity activities
     */
    public function getActivities(int $opportunityId, int $page = 1, int $limit = 20): ?array
    {
        $opportunity = Opportunity::find($opportunityId);
        
        if (!$opportunity) {
            return null;
        }

        $query = $opportunity->activities()->with(['responsavel']);
        $total = $query->count();

        $offset = ($page - 1) * $limit;
        $activities = $query->orderBy('created_at', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        $data = $activities->map(function ($activity) {
            return [
                'id' => $activity->id,
                'titulo' => $activity->titulo,
                'tipo' => $activity->tipo,
                'descricao' => $activity->descricao,
                'status' => $activity->status,
                'data_vencimento' => $activity->data_vencimento?->format('Y-m-d H:i:s'),
                'responsavel' => $activity->responsavel?->nome,
                'created_at' => $activity->created_at->format('Y-m-d H:i:s')
            ];
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
     * Get opportunity statistics
     */
    public function getStats(?int $userId = null): array
    {
        $query = Opportunity::query();
        
        if ($userId) {
            $query->where('usuario_responsavel_id', $userId);
        }

        // Basic counts
        $totalOpportunities = $query->count();
        $activeOpportunities = $query->where('status', 'ativa')->count();
        $wonOpportunities = $query->where('status', 'fechada_ganha')->count();
        $lostOpportunities = $query->where('status', 'fechada_perdida')->count();

        // Value calculations
        $totalValue = $query->sum('valor');
        $wonValue = $query->where('status', 'fechada_ganha')->sum('valor');
        $lostValue = $query->where('status', 'fechada_perdida')->sum('valor');
        $pipelineValue = $query->where('status', 'ativa')->sum('valor');

        // Weighted pipeline value
        $weightedValue = $query->where('status', 'ativa')
            ->get()
            ->sum(function ($opp) {
                return $opp->valor * ($opp->probabilidade / 100);
            });

        // Win rate
        $closedOpportunities = $wonOpportunities + $lostOpportunities;
        $winRate = $closedOpportunities > 0 ? round(($wonOpportunities / $closedOpportunities) * 100, 2) : 0;

        // Average deal size
        $avgDealSize = $totalOpportunities > 0 ? $totalValue / $totalOpportunities : 0;

        // Stage distribution
        $stageDistribution = $query->where('status', 'ativa')
            ->selectRaw('estagio, COUNT(*) as count, SUM(valor) as total_value')
            ->groupBy('estagio')
            ->get()
            ->keyBy('estagio');

        return [
            'overview' => [
                'total_opportunities' => $totalOpportunities,
                'active_opportunities' => $activeOpportunities,
                'won_opportunities' => $wonOpportunities,
                'lost_opportunities' => $lostOpportunities,
                'win_rate' => $winRate
            ],
            'revenue' => [
                'total_value' => $totalValue,
                'won_value' => $wonValue,
                'lost_value' => $lostValue,
                'pipeline_value' => $pipelineValue,
                'weighted_pipeline_value' => $weightedValue,
                'average_deal_size' => $avgDealSize
            ],
            'pipeline_distribution' => $stageDistribution
        ];
    }

    /**
     * Get default probability for stage
     */
    private function getDefaultProbability(string $stage): int
    {
        $probabilities = [
            'prospeccao' => 10,
            'qualificacao' => 25,
            'proposta' => 50,
            'negociacao' => 75,
            'fechamento' => 90
        ];

        return $probabilities[$stage] ?? 10;
    }

    /**
     * Validate opportunity input data
     */
    private function validateOpportunityData(array $data, ?int $opportunityId = null): void
    {
        // Required fields for creation
        if ($opportunityId === null) {
            if (empty($data['titulo'])) {
                throw new \InvalidArgumentException('Opportunity title is required');
            }
            if (empty($data['valor']) || !is_numeric($data['valor'])) {
                throw new \InvalidArgumentException('Opportunity value is required and must be numeric');
            }
        }

        // Value validation
        if (!empty($data['valor'])) {
            if (!is_numeric($data['valor']) || $data['valor'] < 0) {
                throw new \InvalidArgumentException('Opportunity value must be a positive number');
            }
        }

        // Stage validation
        if (!empty($data['estagio']) && !in_array($data['estagio'], self::PIPELINE_STAGES)) {
            throw new \InvalidArgumentException('Invalid stage specified');
        }

        // Status validation
        if (!empty($data['status']) && !in_array($data['status'], self::VALID_STATUSES)) {
            throw new \InvalidArgumentException('Invalid status specified');
        }

        // Probability validation
        if (!empty($data['probabilidade'])) {
            if (!is_numeric($data['probabilidade']) || $data['probabilidade'] < 0 || $data['probabilidade'] > 100) {
                throw new \InvalidArgumentException('Probability must be between 0 and 100');
            }
        }

        // Date validation
        if (!empty($data['data_fechamento_prevista'])) {
            try {
                new \DateTime($data['data_fechamento_prevista']);
            } catch (\Exception $e) {
                throw new \InvalidArgumentException('Invalid expected closing date format');
            }
        }

        // Title length validation
        if (!empty($data['titulo']) && strlen($data['titulo']) > 255) {
            throw new \InvalidArgumentException('Opportunity title is too long');
        }
    }

    /**
     * Transform opportunity data for API response
     */
    private function transformOpportunityData(Opportunity $opportunity, bool $includeDetails = false): array
    {
        $data = [
            'id' => $opportunity->id,
            'titulo' => $opportunity->titulo,
            'descricao' => $opportunity->descricao,
            'valor' => $opportunity->valor,
            'estagio' => $opportunity->estagio,
            'status' => $opportunity->status,
            'probabilidade' => $opportunity->probabilidade,
            'data_fechamento_prevista' => $opportunity->data_fechamento_prevista?->format('Y-m-d'),
            'data_fechamento' => $opportunity->data_fechamento?->format('Y-m-d'),
            'observacoes' => $opportunity->observacoes,
            'empresa_id' => $opportunity->empresa_id,
            'contato_id' => $opportunity->contato_id,
            'usuario_responsavel_id' => $opportunity->usuario_responsavel_id,
            'company' => $opportunity->company ? [
                'id' => $opportunity->company->id,
                'nome' => $opportunity->company->nome
            ] : null,
            'contact' => $opportunity->contact ? [
                'id' => $opportunity->contact->id,
                'nome' => $opportunity->contact->nome,
                'email' => $opportunity->contact->email
            ] : null,
            'responsavel' => $opportunity->responsavel ? [
                'id' => $opportunity->responsavel->id,
                'nome' => $opportunity->responsavel->nome
            ] : null,
            'created_at' => $opportunity->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $opportunity->updated_at->format('Y-m-d H:i:s')
        ];

        if ($includeDetails) {
            $data['weighted_value'] = $opportunity->valor * ($opportunity->probabilidade / 100);
            $data['activities_count'] = $opportunity->activities()->count();
            $data['can_move_stage'] = $opportunity->status === 'ativa';
        }

        return $data;
    }

    /**
     * Search opportunities by title or company name
     */
    public function search(string $query, int $limit = 10): Collection
    {
        $searchTerm = '%' . $query . '%';
        
        return Opportunity::with(['company', 'responsavel'])
            ->where(function ($q) use ($searchTerm) {
                $q->where('titulo', 'like', $searchTerm)
                  ->orWhere('descricao', 'like', $searchTerm)
                  ->orWhereHas('company', function ($companyQuery) use ($searchTerm) {
                      $companyQuery->where('nome', 'like', $searchTerm);
                  });
            })
            ->limit($limit)
            ->get();
    }
}