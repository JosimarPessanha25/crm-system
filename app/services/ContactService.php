<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Contact;
use App\Models\Company;
use App\Models\Activity;
use Illuminate\Database\Eloquent\Collection;
use Psr\Log\LoggerInterface;

/**
 * ContactService - Business logic for contact management
 * 
 * Handles all contact-related business operations including validation,
 * scoring, tagging, timeline management, and relationship handling.
 */
class ContactService
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * List contacts with pagination and filtering
     */
    public function list(int $page = 1, int $limit = 20, array $filters = []): array
    {
        $query = Contact::with(['company']);

        // Apply search filter
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('nome', 'like', $search)
                  ->orWhere('email', 'like', $search)
                  ->orWhere('telefone', 'like', $search)
                  ->orWhere('cargo', 'like', $search);
            });
        }

        // Apply company filter
        if (!empty($filters['company_id'])) {
            $query->where('empresa_id', $filters['company_id']);
        }

        // Apply score range filter
        if (!empty($filters['min_score'])) {
            $query->where('score', '>=', $filters['min_score']);
        }
        if (!empty($filters['max_score'])) {
            $query->where('score', '<=', $filters['max_score']);
        }

        // Apply tag filter
        if (!empty($filters['tag'])) {
            $query->where('tags', 'like', '%' . $filters['tag'] . '%');
        }

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        // Get total count for pagination
        $total = $query->count();

        // Apply pagination
        $offset = ($page - 1) * $limit;
        $contacts = $query->offset($offset)->limit($limit)->get();

        // Transform data
        $data = $contacts->map(function ($contact) {
            return $this->transformContactData($contact);
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
     * Find contact by ID
     */
    public function findById(int $id): ?array
    {
        $contact = Contact::with(['company', 'opportunities', 'activities'])->find($id);
        
        if (!$contact) {
            return null;
        }

        return $this->transformContactData($contact, true);
    }

    /**
     * Create a new contact
     */
    public function create(array $data): array
    {
        $this->validateContactData($data);
        
        // Check for email uniqueness if provided
        if (!empty($data['email'])) {
            if (Contact::where('email', $data['email'])->exists()) {
                throw new \InvalidArgumentException('Email already exists');
            }
        }

        // Calculate initial score
        $data['score'] = $this->calculateContactScore($data);

        // Validate company exists
        if (!empty($data['empresa_id'])) {
            if (!Company::find($data['empresa_id'])) {
                throw new \InvalidArgumentException('Company not found');
            }
        }

        $contact = Contact::create($data);

        $this->logger->info('Contact created', [
            'contact_id' => $contact->id,
            'nome' => $contact->nome,
            'email' => $contact->email,
            'empresa_id' => $contact->empresa_id
        ]);

        return $this->transformContactData($contact);
    }

    /**
     * Update an existing contact
     */
    public function update(int $id, array $data): ?array
    {
        $contact = Contact::find($id);
        
        if (!$contact) {
            return null;
        }

        $this->validateContactData($data, $id);

        // Check email uniqueness if being changed
        if (!empty($data['email'])) {
            if (Contact::where('email', $data['email'])->where('id', '!=', $id)->exists()) {
                throw new \InvalidArgumentException('Email already exists');
            }
        }

        // Validate company exists if being changed
        if (!empty($data['empresa_id'])) {
            if (!Company::find($data['empresa_id'])) {
                throw new \InvalidArgumentException('Company not found');
            }
        }

        // Recalculate score if relevant data changed
        if (array_intersect_key($data, array_flip(['cargo', 'departamento', 'telefone', 'linkedin']))) {
            $scoreData = array_merge($contact->toArray(), $data);
            $data['score'] = $this->calculateContactScore($scoreData);
        }

        $contact->update($data);

        $this->logger->info('Contact updated', [
            'contact_id' => $contact->id,
            'changes' => array_keys($data)
        ]);

        return $this->transformContactData($contact->fresh());
    }

    /**
     * Delete a contact (soft delete)
     */
    public function delete(int $id): bool
    {
        $contact = Contact::find($id);
        
        if (!$contact) {
            return false;
        }

        // Check if contact has active opportunities
        $activeOpportunities = $contact->opportunities()
            ->whereNotIn('status', ['fechada_ganha', 'fechada_perdida'])
            ->count();

        if ($activeOpportunities > 0) {
            throw new \InvalidArgumentException('Cannot delete contact with active opportunities');
        }

        $contact->delete();

        $this->logger->info('Contact deleted', [
            'contact_id' => $id,
            'nome' => $contact->nome
        ]);

        return true;
    }

    /**
     * Get contact timeline (activities and opportunities)
     */
    public function getTimeline(int $contactId, int $page = 1, int $limit = 20): ?array
    {
        $contact = Contact::find($contactId);
        
        if (!$contact) {
            return null;
        }

        // Get activities
        $activities = $contact->activities()
            ->with(['responsavel'])
            ->select(['id', 'tipo', 'titulo', 'descricao', 'status', 'created_at', 'usuario_responsavel_id'])
            ->get()
            ->map(function ($activity) {
                return [
                    'type' => 'activity',
                    'id' => $activity->id,
                    'titulo' => $activity->titulo,
                    'descricao' => $activity->descricao,
                    'tipo' => $activity->tipo,
                    'status' => $activity->status,
                    'responsavel' => $activity->responsavel?->nome,
                    'date' => $activity->created_at
                ];
            });

        // Get opportunities
        $opportunities = $contact->opportunities()
            ->with(['responsavel'])
            ->select(['id', 'titulo', 'valor', 'status', 'estagio', 'created_at', 'usuario_responsavel_id'])
            ->get()
            ->map(function ($opportunity) {
                return [
                    'type' => 'opportunity',
                    'id' => $opportunity->id,
                    'titulo' => $opportunity->titulo,
                    'valor' => $opportunity->valor,
                    'status' => $opportunity->status,
                    'estagio' => $opportunity->estagio,
                    'responsavel' => $opportunity->responsavel?->nome,
                    'date' => $opportunity->created_at
                ];
            });

        // Merge and sort by date
        $timeline = $activities->concat($opportunities)
            ->sortByDesc('date')
            ->values();

        // Apply pagination
        $total = $timeline->count();
        $offset = ($page - 1) * $limit;
        $paginatedTimeline = $timeline->slice($offset, $limit)->values();

        return [
            'data' => $paginatedTimeline,
            'current_page' => $page,
            'per_page' => $limit,
            'total' => $total,
            'total_pages' => ceil($total / $limit)
        ];
    }

    /**
     * Get contact opportunities
     */
    public function getOpportunities(int $contactId, int $page = 1, int $limit = 20): ?array
    {
        $contact = Contact::find($contactId);
        
        if (!$contact) {
            return null;
        }

        $query = $contact->opportunities()->with(['responsavel']);
        $total = $query->count();

        $offset = ($page - 1) * $limit;
        $opportunities = $query->offset($offset)->limit($limit)->get();

        $data = $opportunities->map(function ($opportunity) {
            return [
                'id' => $opportunity->id,
                'titulo' => $opportunity->titulo,
                'valor' => $opportunity->valor,
                'status' => $opportunity->status,
                'estagio' => $opportunity->estagio,
                'probabilidade' => $opportunity->probabilidade,
                'data_fechamento_prevista' => $opportunity->data_fechamento_prevista?->format('Y-m-d'),
                'responsavel' => $opportunity->responsavel?->nome,
                'created_at' => $opportunity->created_at->format('Y-m-d H:i:s')
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
     * Update contact score
     */
    public function updateScore(int $id, int $score): ?array
    {
        $contact = Contact::find($id);
        
        if (!$contact) {
            return null;
        }

        if ($score < 0 || $score > 100) {
            throw new \InvalidArgumentException('Score must be between 0 and 100');
        }

        $contact->update(['score' => $score]);

        $this->logger->info('Contact score updated', [
            'contact_id' => $id,
            'old_score' => $contact->getOriginal('score'),
            'new_score' => $score
        ]);

        return $this->transformContactData($contact);
    }

    /**
     * Add tags to contact
     */
    public function addTags(int $id, array $tags): ?array
    {
        $contact = Contact::find($id);
        
        if (!$contact) {
            return null;
        }

        // Get existing tags
        $existingTags = $contact->tags ? explode(',', $contact->tags) : [];
        
        // Merge with new tags and remove duplicates
        $allTags = array_unique(array_merge($existingTags, $tags));
        
        // Filter out empty tags and trim whitespace
        $cleanTags = array_filter(array_map('trim', $allTags));
        
        $contact->update(['tags' => implode(',', $cleanTags)]);

        $this->logger->info('Tags added to contact', [
            'contact_id' => $id,
            'new_tags' => $tags,
            'all_tags' => $cleanTags
        ]);

        return $this->transformContactData($contact);
    }

    /**
     * Remove tags from contact
     */
    public function removeTags(int $id, array $tags): ?array
    {
        $contact = Contact::find($id);
        
        if (!$contact) {
            return null;
        }

        // Get existing tags
        $existingTags = $contact->tags ? explode(',', $contact->tags) : [];
        
        // Remove specified tags
        $remainingTags = array_diff($existingTags, $tags);
        
        $contact->update(['tags' => implode(',', $remainingTags)]);

        $this->logger->info('Tags removed from contact', [
            'contact_id' => $id,
            'removed_tags' => $tags,
            'remaining_tags' => $remainingTags
        ]);

        return $this->transformContactData($contact);
    }

    /**
     * Calculate contact score based on available data
     */
    private function calculateContactScore(array $data): int
    {
        $score = 0;

        // Base score for having basic information
        if (!empty($data['nome'])) $score += 10;
        if (!empty($data['email'])) $score += 20;
        if (!empty($data['telefone'])) $score += 15;
        
        // Position-based scoring
        if (!empty($data['cargo'])) {
            $cargo = strtolower($data['cargo']);
            if (strpos($cargo, 'diretor') !== false || strpos($cargo, 'ceo') !== false) {
                $score += 25;
            } elseif (strpos($cargo, 'gerente') !== false || strpos($cargo, 'manager') !== false) {
                $score += 20;
            } elseif (strpos($cargo, 'coordenador') !== false || strpos($cargo, 'supervisor') !== false) {
                $score += 15;
            } else {
                $score += 10;
            }
        }

        // Department-based scoring
        if (!empty($data['departamento'])) {
            $departamento = strtolower($data['departamento']);
            if (in_array($departamento, ['vendas', 'comercial', 'compras'])) {
                $score += 10;
            }
        }

        // Social media presence
        if (!empty($data['linkedin'])) $score += 10;

        // Company association
        if (!empty($data['empresa_id'])) $score += 10;

        return min($score, 100); // Cap at 100
    }

    /**
     * Validate contact input data
     */
    private function validateContactData(array $data, ?int $contactId = null): void
    {
        // Required fields for creation
        if ($contactId === null) {
            if (empty($data['nome'])) {
                throw new \InvalidArgumentException('Contact name is required');
            }
        }

        // Email format validation
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email format');
        }

        // Phone validation
        if (!empty($data['telefone'])) {
            $phone = preg_replace('/[^0-9]/', '', $data['telefone']);
            if (strlen($phone) < 10 || strlen($phone) > 11) {
                throw new \InvalidArgumentException('Invalid phone number format');
            }
        }

        // Score validation
        if (!empty($data['score'])) {
            if (!is_numeric($data['score']) || $data['score'] < 0 || $data['score'] > 100) {
                throw new \InvalidArgumentException('Score must be between 0 and 100');
            }
        }

        // LinkedIn URL validation
        if (!empty($data['linkedin'])) {
            if (!filter_var($data['linkedin'], FILTER_VALIDATE_URL)) {
                throw new \InvalidArgumentException('Invalid LinkedIn URL format');
            }
        }

        // Name length validation
        if (!empty($data['nome']) && strlen($data['nome']) > 255) {
            throw new \InvalidArgumentException('Contact name is too long');
        }
    }

    /**
     * Transform contact data for API response
     */
    private function transformContactData(Contact $contact, bool $includeDetails = false): array
    {
        $data = [
            'id' => $contact->id,
            'nome' => $contact->nome,
            'email' => $contact->email,
            'telefone' => $contact->telefone,
            'cargo' => $contact->cargo,
            'departamento' => $contact->departamento,
            'score' => $contact->score,
            'tags' => $contact->tags ? explode(',', $contact->tags) : [],
            'linkedin' => $contact->linkedin,
            'observacoes' => $contact->observacoes,
            'empresa_id' => $contact->empresa_id,
            'company' => $contact->company ? [
                'id' => $contact->company->id,
                'nome' => $contact->company->nome
            ] : null,
            'created_at' => $contact->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $contact->updated_at->format('Y-m-d H:i:s')
        ];

        if ($includeDetails) {
            $data['stats'] = [
                'opportunities_count' => $contact->opportunities()->count(),
                'activities_count' => $contact->activities()->count(),
                'total_opportunity_value' => $contact->opportunities()->sum('valor'),
                'won_opportunities' => $contact->opportunities()->where('status', 'fechada_ganha')->count(),
                'active_opportunities' => $contact->opportunities()
                    ->whereNotIn('status', ['fechada_ganha', 'fechada_perdida'])
                    ->count()
            ];
        }

        return $data;
    }

    /**
     * Search contacts by name, email, or company
     */
    public function search(string $query, int $limit = 10): Collection
    {
        $searchTerm = '%' . $query . '%';
        
        return Contact::with(['company'])
            ->where(function ($q) use ($searchTerm) {
                $q->where('nome', 'like', $searchTerm)
                  ->orWhere('email', 'like', $searchTerm)
                  ->orWhereHas('company', function ($companyQuery) use ($searchTerm) {
                      $companyQuery->where('nome', 'like', $searchTerm);
                  });
            })
            ->limit($limit)
            ->get();
    }

    /**
     * Get contacts by score range
     */
    public function getContactsByScore(int $minScore, int $maxScore): Collection
    {
        return Contact::with(['company'])
            ->whereBetween('score', [$minScore, $maxScore])
            ->orderBy('score', 'desc')
            ->get();
    }

    /**
     * Get contacts by tag
     */
    public function getContactsByTag(string $tag): Collection
    {
        return Contact::with(['company'])
            ->where('tags', 'like', '%' . $tag . '%')
            ->get();
    }
}