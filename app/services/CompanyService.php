<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Opportunity;
use Illuminate\Database\Eloquent\Collection;
use Psr\Log\LoggerInterface;

/**
 * CompanyService - Business logic for company management
 * 
 * Handles all company-related business operations including validation,
 * data processing, and complex business rules.
 */
class CompanyService
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * List companies with pagination and filtering
     */
    public function list(int $page = 1, int $limit = 20, array $filters = []): array
    {
        $query = Company::query();

        // Apply search filter
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('nome', 'like', $search)
                  ->orWhere('cnpj', 'like', $search)
                  ->orWhere('email', 'like', $search)
                  ->orWhere('telefone', 'like', $search);
            });
        }

        // Apply status filter
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Apply sector filter
        if (!empty($filters['sector'])) {
            $query->where('setor', $filters['sector']);
        }

        // Apply size filter
        if (!empty($filters['size'])) {
            $query->where('porte', $filters['size']);
        }

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        // Get total count for pagination
        $total = $query->count();

        // Apply pagination
        $offset = ($page - 1) * $limit;
        $companies = $query->offset($offset)->limit($limit)->get();

        // Transform data
        $data = $companies->map(function ($company) {
            return $this->transformCompanyData($company);
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
     * Find company by ID
     */
    public function findById(int $id): ?array
    {
        $company = Company::with(['contacts', 'opportunities'])->find($id);
        
        if (!$company) {
            return null;
        }

        return $this->transformCompanyData($company, true);
    }

    /**
     * Create a new company
     */
    public function create(array $data): array
    {
        $this->validateCompanyData($data);
        
        // Check for CNPJ uniqueness if provided
        if (!empty($data['cnpj'])) {
            $cleanCnpj = $this->cleanCnpj($data['cnpj']);
            if (Company::where('cnpj', $cleanCnpj)->exists()) {
                throw new \InvalidArgumentException('CNPJ already exists');
            }
            $data['cnpj'] = $cleanCnpj;
        }

        // Set default status
        $data['status'] = $data['status'] ?? 'ativa';

        $company = Company::create($data);

        $this->logger->info('Company created', [
            'company_id' => $company->id,
            'nome' => $company->nome,
            'cnpj' => $company->cnpj
        ]);

        return $this->transformCompanyData($company);
    }

    /**
     * Update an existing company
     */
    public function update(int $id, array $data): ?array
    {
        $company = Company::find($id);
        
        if (!$company) {
            return null;
        }

        $this->validateCompanyData($data, $id);

        // Check CNPJ uniqueness if being changed
        if (!empty($data['cnpj'])) {
            $cleanCnpj = $this->cleanCnpj($data['cnpj']);
            if (Company::where('cnpj', $cleanCnpj)->where('id', '!=', $id)->exists()) {
                throw new \InvalidArgumentException('CNPJ already exists');
            }
            $data['cnpj'] = $cleanCnpj;
        }

        $company->update($data);

        $this->logger->info('Company updated', [
            'company_id' => $company->id,
            'changes' => array_keys($data)
        ]);

        return $this->transformCompanyData($company->fresh());
    }

    /**
     * Delete a company (soft delete)
     */
    public function delete(int $id): bool
    {
        $company = Company::find($id);
        
        if (!$company) {
            return false;
        }

        // Check if company has active opportunities
        $activeOpportunities = $company->opportunities()
            ->whereNotIn('status', ['fechada_ganha', 'fechada_perdida'])
            ->count();

        if ($activeOpportunities > 0) {
            throw new \InvalidArgumentException('Cannot delete company with active opportunities');
        }

        $company->delete();

        $this->logger->info('Company deleted', [
            'company_id' => $id,
            'nome' => $company->nome
        ]);

        return true;
    }

    /**
     * Get company contacts with pagination
     */
    public function getContacts(int $companyId, int $page = 1, int $limit = 20): ?array
    {
        $company = Company::find($companyId);
        
        if (!$company) {
            return null;
        }

        $query = $company->contacts();
        $total = $query->count();

        $offset = ($page - 1) * $limit;
        $contacts = $query->offset($offset)->limit($limit)->get();

        $data = $contacts->map(function ($contact) {
            return [
                'id' => $contact->id,
                'nome' => $contact->nome,
                'email' => $contact->email,
                'telefone' => $contact->telefone,
                'cargo' => $contact->cargo,
                'score' => $contact->score,
                'tags' => $contact->tags ? explode(',', $contact->tags) : [],
                'created_at' => $contact->created_at->format('Y-m-d H:i:s')
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
     * Get company opportunities with pagination
     */
    public function getOpportunities(int $companyId, int $page = 1, int $limit = 20): ?array
    {
        $company = Company::find($companyId);
        
        if (!$company) {
            return null;
        }

        $query = $company->opportunities();
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
     * Get company statistics
     */
    public function getStats(int $id): ?array
    {
        $company = Company::find($id);
        
        if (!$company) {
            return null;
        }

        // Get counts
        $contactsCount = $company->contacts()->count();
        $opportunitiesCount = $company->opportunities()->count();
        $activitiesCount = $company->activities()->count();

        // Calculate opportunity values
        $totalOpportunityValue = $company->opportunities()->sum('valor');
        $wonOpportunityValue = $company->opportunities()->where('status', 'fechada_ganha')->sum('valor');
        $lostOpportunityValue = $company->opportunities()->where('status', 'fechada_perdida')->sum('valor');
        $pipelineValue = $company->opportunities()
            ->whereNotIn('status', ['fechada_ganha', 'fechada_perdida'])
            ->sum('valor');

        // Get opportunity distribution by stage
        $opportunityByStage = $company->opportunities()
            ->selectRaw('estagio, COUNT(*) as count, SUM(valor) as total_value')
            ->whereNotIn('status', ['fechada_ganha', 'fechada_perdida'])
            ->groupBy('estagio')
            ->get()
            ->keyBy('estagio');

        // Get recent activities
        $recentActivities = $company->activities()
            ->with('responsavel')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'titulo' => $activity->titulo,
                    'tipo' => $activity->tipo,
                    'status' => $activity->status,
                    'data_vencimento' => $activity->data_vencimento?->format('Y-m-d'),
                    'responsavel' => $activity->responsavel?->nome,
                    'created_at' => $activity->created_at->format('Y-m-d H:i:s')
                ];
            });

        return [
            'company_id' => $company->id,
            'overview' => [
                'contacts_count' => $contactsCount,
                'opportunities_count' => $opportunitiesCount,
                'activities_count' => $activitiesCount
            ],
            'revenue' => [
                'total_opportunity_value' => $totalOpportunityValue,
                'won_value' => $wonOpportunityValue,
                'lost_value' => $lostOpportunityValue,
                'pipeline_value' => $pipelineValue,
                'win_rate' => $totalOpportunityValue > 0 ? 
                    round(($wonOpportunityValue / $totalOpportunityValue) * 100, 2) : 0
            ],
            'pipeline_distribution' => $opportunityByStage,
            'recent_activities' => $recentActivities
        ];
    }

    /**
     * Validate company input data
     */
    private function validateCompanyData(array $data, ?int $companyId = null): void
    {
        // Required fields for creation
        if ($companyId === null) {
            if (empty($data['nome'])) {
                throw new \InvalidArgumentException('Company name is required');
            }
        }

        // Email format validation
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email format');
        }

        // CNPJ validation
        if (!empty($data['cnpj']) && !$this->isValidCnpj($data['cnpj'])) {
            throw new \InvalidArgumentException('Invalid CNPJ format');
        }

        // Phone validation
        if (!empty($data['telefone'])) {
            $phone = preg_replace('/[^0-9]/', '', $data['telefone']);
            if (strlen($phone) < 10 || strlen($phone) > 11) {
                throw new \InvalidArgumentException('Invalid phone number format');
            }
        }

        // Status validation
        if (!empty($data['status'])) {
            $allowedStatuses = ['ativa', 'inativa', 'prospect', 'cliente'];
            if (!in_array($data['status'], $allowedStatuses)) {
                throw new \InvalidArgumentException('Invalid status specified');
            }
        }

        // Company size validation
        if (!empty($data['porte'])) {
            $allowedSizes = ['micro', 'pequena', 'media', 'grande'];
            if (!in_array($data['porte'], $allowedSizes)) {
                throw new \InvalidArgumentException('Invalid company size specified');
            }
        }

        // Name length validation
        if (!empty($data['nome']) && strlen($data['nome']) > 255) {
            throw new \InvalidArgumentException('Company name is too long');
        }
    }

    /**
     * Transform company data for API response
     */
    private function transformCompanyData(Company $company, bool $includeDetails = false): array
    {
        $data = [
            'id' => $company->id,
            'nome' => $company->nome,
            'cnpj' => $company->cnpj,
            'email' => $company->email,
            'telefone' => $company->telefone,
            'endereco' => $company->endereco,
            'setor' => $company->setor,
            'porte' => $company->porte,
            'status' => $company->status,
            'website' => $company->website,
            'observacoes' => $company->observacoes,
            'created_at' => $company->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $company->updated_at->format('Y-m-d H:i:s')
        ];

        if ($includeDetails) {
            $data['stats'] = [
                'contacts_count' => $company->contacts()->count(),
                'opportunities_count' => $company->opportunities()->count(),
                'activities_count' => $company->activities()->count(),
                'total_opportunity_value' => $company->opportunities()->sum('valor')
            ];

            // Include recent contacts
            $data['recent_contacts'] = $company->contacts()
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($contact) {
                    return [
                        'id' => $contact->id,
                        'nome' => $contact->nome,
                        'email' => $contact->email,
                        'cargo' => $contact->cargo
                    ];
                });
        }

        return $data;
    }

    /**
     * Clean CNPJ (remove formatting)
     */
    private function cleanCnpj(string $cnpj): string
    {
        return preg_replace('/[^0-9]/', '', $cnpj);
    }

    /**
     * Validate CNPJ format and check digit
     */
    private function isValidCnpj(string $cnpj): bool
    {
        $cnpj = $this->cleanCnpj($cnpj);
        
        // Check if has 14 digits
        if (strlen($cnpj) !== 14) {
            return false;
        }

        // Check if all digits are the same
        if (preg_match('/^(\d)\1*$/', $cnpj)) {
            return false;
        }

        // Validate check digits
        $sum = 0;
        $weights = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        
        for ($i = 0; $i < 12; $i++) {
            $sum += intval($cnpj[$i]) * $weights[$i];
        }
        
        $remainder = $sum % 11;
        $checkDigit1 = $remainder < 2 ? 0 : 11 - $remainder;
        
        if (intval($cnpj[12]) !== $checkDigit1) {
            return false;
        }
        
        $sum = 0;
        $weights = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        
        for ($i = 0; $i < 13; $i++) {
            $sum += intval($cnpj[$i]) * $weights[$i];
        }
        
        $remainder = $sum % 11;
        $checkDigit2 = $remainder < 2 ? 0 : 11 - $remainder;
        
        return intval($cnpj[13]) === $checkDigit2;
    }

    /**
     * Search companies by name or CNPJ
     */
    public function search(string $query, int $limit = 10): Collection
    {
        $searchTerm = '%' . $query . '%';
        $cnpjSearch = $this->cleanCnpj($query);
        
        return Company::where('nome', 'like', $searchTerm)
            ->orWhere('cnpj', 'like', $cnpjSearch . '%')
            ->limit($limit)
            ->get();
    }

    /**
     * Get companies by status
     */
    public function getCompaniesByStatus(string $status): Collection
    {
        return Company::where('status', $status)
            ->orderBy('nome')
            ->get();
    }
}