<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Ramsey\Uuid\Uuid;

/**
 * @property string $id
 * @property string $titulo
 * @property ?string $descricao
 * @property float $valor
 * @property string $moeda
 * @property string $estagio
 * @property int $probabilidade
 * @property ?string $data_fechamento_previsto
 * @property ?string $data_fechamento_real
 * @property ?string $contato_id
 * @property ?string $empresa_id
 * @property string $owner_id
 * @property ?string $origem
 * @property ?string $concorrentes
 * @property ?string $observacoes
 * @property string $created_at
 * @property string $updated_at
 */
class Opportunity extends Model
{
    protected $table = 'oportunidades';
    protected $keyType = 'string';
    public $incrementing = false;
    
    protected $fillable = [
        'titulo',
        'descricao',
        'valor',
        'moeda',
        'estagio',
        'probabilidade',
        'data_fechamento_previsto',
        'data_fechamento_real',
        'contato_id',
        'empresa_id',
        'owner_id',
        'origem',
        'concorrentes',
        'observacoes'
    ];
    
    protected $casts = [
        'valor' => 'float',
        'probabilidade' => 'integer',
        'data_fechamento_previsto' => 'date',
        'data_fechamento_real' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    public const ESTAGIO_PROSPECTING = 'prospecting';
    public const ESTAGIO_QUALIFICATION = 'qualification';
    public const ESTAGIO_PROPOSAL = 'proposal';
    public const ESTAGIO_NEGOTIATION = 'negotiation';
    public const ESTAGIO_CLOSED_WON = 'closed_won';
    public const ESTAGIO_CLOSED_LOST = 'closed_lost';
    
    public const ESTAGIOS = [
        self::ESTAGIO_PROSPECTING => 'Prospecção',
        self::ESTAGIO_QUALIFICATION => 'Qualificação',
        self::ESTAGIO_PROPOSAL => 'Proposta',
        self::ESTAGIO_NEGOTIATION => 'Negociação',
        self::ESTAGIO_CLOSED_WON => 'Fechado - Ganho',
        self::ESTAGIO_CLOSED_LOST => 'Fechado - Perdido'
    ];
    
    public const ESTAGIOS_ORDEM = [
        self::ESTAGIO_PROSPECTING,
        self::ESTAGIO_QUALIFICATION,
        self::ESTAGIO_PROPOSAL,
        self::ESTAGIO_NEGOTIATION,
        self::ESTAGIO_CLOSED_WON,
        self::ESTAGIO_CLOSED_LOST
    ];
    
    public const PROBABILIDADES_DEFAULT = [
        self::ESTAGIO_PROSPECTING => 20,
        self::ESTAGIO_QUALIFICATION => 40,
        self::ESTAGIO_PROPOSAL => 60,
        self::ESTAGIO_NEGOTIATION => 80,
        self::ESTAGIO_CLOSED_WON => 100,
        self::ESTAGIO_CLOSED_LOST => 0
    ];
    
    protected static function boot(): void
    {
        parent::boot();
        
        static::creating(function (self $model): void {
            if (!$model->getKey()) {
                $model->{$model->getKeyName()} = Uuid::uuid4()->toString();
            }
            
            // Set default probability based on stage
            if ($model->estagio && !$model->probabilidade) {
                $model->probabilidade = self::PROBABILIDADES_DEFAULT[$model->estagio] ?? 20;
            }
        });
        
        static::updating(function (self $model): void {
            // Update probability when stage changes
            if ($model->isDirty('estagio')) {
                $model->probabilidade = self::PROBABILIDADES_DEFAULT[$model->estagio] ?? $model->probabilidade;
                
                // Set closing date for won/lost opportunities
                if (in_array($model->estagio, [self::ESTAGIO_CLOSED_WON, self::ESTAGIO_CLOSED_LOST]) && !$model->data_fechamento_real) {
                    $model->data_fechamento_real = now()->format('Y-m-d');
                }
            }
        });
    }
    
    // Relationships
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
    
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contato_id');
    }
    
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'empresa_id');
    }
    
    public function activities(): MorphMany
    {
        return $this->morphMany(Activity::class, 'relacionado', 'relacionado_tipo', 'relacionado_id');
    }
    
    public function interactions(): MorphMany
    {
        return $this->morphMany(Interaction::class, 'relacionado', 'relacionado_tipo', 'relacionado_id');
    }
    
    // Scopes
    public function scopeByOwner($query, string $ownerId)
    {
        return $query->where('owner_id', $ownerId);
    }
    
    public function scopeByStage($query, string $stage)
    {
        return $query->where('estagio', $stage);
    }
    
    public function scopeActive($query)
    {
        return $query->whereNotIn('estagio', [self::ESTAGIO_CLOSED_WON, self::ESTAGIO_CLOSED_LOST]);
    }
    
    public function scopeWon($query)
    {
        return $query->where('estagio', self::ESTAGIO_CLOSED_WON);
    }
    
    public function scopeLost($query)
    {
        return $query->where('estagio', self::ESTAGIO_CLOSED_LOST);
    }
    
    public function scopeByValueRange($query, float $min, float $max)
    {
        return $query->whereBetween('valor', [$min, $max]);
    }
    
    public function scopeExpectedClosingThis($query, string $period = 'month')
    {
        $start = match ($period) {
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'quarter' => now()->startOfQuarter(),
            'year' => now()->startOfYear(),
            default => now()->startOfMonth()
        };
        
        $end = match ($period) {
            'week' => now()->endOfWeek(),
            'month' => now()->endOfMonth(),
            'quarter' => now()->endOfQuarter(),
            'year' => now()->endOfYear(),
            default => now()->endOfMonth()
        };
        
        return $query->whereBetween('data_fechamento_previsto', [$start->format('Y-m-d'), $end->format('Y-m-d')]);
    }
    
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('titulo', 'LIKE', "%{$search}%")
              ->orWhere('descricao', 'LIKE', "%{$search}%")
              ->orWhereHas('contact', function ($contact) use ($search) {
                  $contact->where('nome', 'LIKE', "%{$search}%");
              })
              ->orWhereHas('company', function ($company) use ($search) {
                  $company->where('nome', 'LIKE', "%{$search}%");
              });
        });
    }
    
    // Accessors
    public function getFormattedValueAttribute(): string
    {
        return 'R$ ' . number_format($this->valor, 2, ',', '.');
    }
    
    public function getEstagioLabelAttribute(): string
    {
        return self::ESTAGIOS[$this->estagio] ?? $this->estagio;
    }
    
    public function getStageBadgeColorAttribute(): string
    {
        return match ($this->estagio) {
            self::ESTAGIO_PROSPECTING => 'primary',
            self::ESTAGIO_QUALIFICATION => 'info',
            self::ESTAGIO_PROPOSAL => 'warning',
            self::ESTAGIO_NEGOTIATION => 'orange',
            self::ESTAGIO_CLOSED_WON => 'success',
            self::ESTAGIO_CLOSED_LOST => 'danger',
            default => 'secondary'
        };
    }
    
    public function getWeightedValueAttribute(): float
    {
        return $this->valor * ($this->probabilidade / 100);
    }
    
    // Helper methods
    public function canMoveToStage(string $newStage): bool
    {
        $currentIndex = array_search($this->estagio, self::ESTAGIOS_ORDEM);
        $newIndex = array_search($newStage, self::ESTAGIOS_ORDEM);
        
        if ($currentIndex === false || $newIndex === false) {
            return false;
        }
        
        // Can move to any stage forward, or to closed_lost from any stage
        return $newIndex > $currentIndex || $newStage === self::ESTAGIO_CLOSED_LOST;
    }
    
    public function moveToStage(string $newStage, ?string $reason = null): bool
    {
        if (!$this->canMoveToStage($newStage)) {
            return false;
        }
        
        $oldStage = $this->estagio;
        $this->estagio = $newStage;
        $this->save();
        
        // Log the stage change as an interaction
        if ($reason) {
            $this->interactions()->create([
                'tipo' => 'note',
                'assunto' => "Estágio alterado: {$oldStage} → {$newStage}",
                'conteudo' => $reason,
                'author_id' => auth()->id() ?? $this->owner_id,
                'interaction_date' => now()
            ]);
        }
        
        return true;
    }
    
    public function isActive(): bool
    {
        return !in_array($this->estagio, [self::ESTAGIO_CLOSED_WON, self::ESTAGIO_CLOSED_LOST]);
    }
    
    public function isWon(): bool
    {
        return $this->estagio === self::ESTAGIO_CLOSED_WON;
    }
    
    public function isLost(): bool
    {
        return $this->estagio === self::ESTAGIO_CLOSED_LOST;
    }
    
    public function getDaysInStage(): int
    {
        $lastStageChange = $this->interactions()
                               ->where('assunto', 'LIKE', 'Estágio alterado:%')
                               ->latest('interaction_date')
                               ->first();
        
        $startDate = $lastStageChange?->interaction_date ?? $this->created_at;
        
        return now()->diffInDays($startDate);
    }
}