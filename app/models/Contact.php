<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Ramsey\Uuid\Uuid;

/**
 * @property string $id
 * @property string $nome
 * @property ?string $email
 * @property ?string $email_secundario
 * @property ?string $telefone
 * @property ?string $telefone_secundario
 * @property ?string $cargo
 * @property ?string $origem
 * @property ?array $tags
 * @property int $lead_score
 * @property ?string $empresa_id
 * @property ?string $owner_id
 * @property ?string $observacoes
 * @property string $created_at
 * @property string $updated_at
 */
class Contact extends Model
{
    protected $table = 'contatos';
    protected $keyType = 'string';
    public $incrementing = false;
    
    protected $fillable = [
        'nome',
        'email',
        'email_secundario',
        'telefone',
        'telefone_secundario',
        'cargo',
        'origem',
        'tags',
        'lead_score',
        'empresa_id',
        'owner_id',
        'observacoes'
    ];
    
    protected $casts = [
        'tags' => 'array',
        'lead_score' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    public const ORIGEM_WEBSITE = 'website';
    public const ORIGEM_INDICACAO = 'indicacao';
    public const ORIGEM_EVENTO = 'evento';
    public const ORIGEM_TELEFONE = 'telefone';
    public const ORIGEM_EMAIL = 'email';
    public const ORIGEM_SOCIAL = 'social';
    
    public const ORIGENS = [
        self::ORIGEM_WEBSITE,
        self::ORIGEM_INDICACAO,
        self::ORIGEM_EVENTO,
        self::ORIGEM_TELEFONE,
        self::ORIGEM_EMAIL,
        self::ORIGEM_SOCIAL
    ];
    
    protected static function boot(): void
    {
        parent::boot();
        
        static::creating(function (self $model): void {
            if (!$model->getKey()) {
                $model->{$model->getKeyName()} = Uuid::uuid4()->toString();
            }
        });
    }
    
    // Relationships
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
    
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'empresa_id');
    }
    
    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class, 'contato_id');
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
    
    public function scopeByCompany($query, string $companyId)
    {
        return $query->where('empresa_id', $companyId);
    }
    
    public function scopeByOrigem($query, string $origem)
    {
        return $query->where('origem', $origem);
    }
    
    public function scopeWithTag($query, string $tag)
    {
        return $query->whereJsonContains('tags', $tag);
    }
    
    public function scopeByScoreRange($query, int $min, int $max)
    {
        return $query->whereBetween('lead_score', [$min, $max]);
    }
    
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('nome', 'LIKE', "%{$search}%")
              ->orWhere('email', 'LIKE', "%{$search}%")
              ->orWhere('email_secundario', 'LIKE', "%{$search}%")
              ->orWhere('telefone', 'LIKE', "%{$search}%")
              ->orWhere('cargo', 'LIKE', "%{$search}%");
        });
    }
    
    // Accessors
    public function getPrimaryEmailAttribute(): ?string
    {
        return $this->email ?: $this->email_secundario;
    }
    
    public function getPrimaryPhoneAttribute(): ?string
    {
        return $this->telefone ?: $this->telefone_secundario;
    }
    
    public function getFormattedPhoneAttribute(): ?string
    {
        $phone = $this->getPrimaryPhoneAttribute();
        
        if (!$phone) {
            return null;
        }
        
        $phone = preg_replace('/\D/', '', $phone);
        
        if (strlen($phone) === 11) {
            return '(' . substr($phone, 0, 2) . ') ' . 
                   substr($phone, 2, 5) . '-' . 
                   substr($phone, 7, 4);
        }
        
        if (strlen($phone) === 10) {
            return '(' . substr($phone, 0, 2) . ') ' . 
                   substr($phone, 2, 4) . '-' . 
                   substr($phone, 6, 4);
        }
        
        return $phone;
    }
    
    public function getScoreBadgeColorAttribute(): string
    {
        return match (true) {
            $this->lead_score >= 80 => 'success',
            $this->lead_score >= 60 => 'warning',
            $this->lead_score >= 40 => 'info',
            default => 'secondary'
        };
    }
    
    // Helper methods
    public function addTag(string $tag): void
    {
        $tags = $this->tags ?? [];
        
        if (!in_array($tag, $tags)) {
            $tags[] = $tag;
            $this->tags = $tags;
        }
    }
    
    public function removeTag(string $tag): void
    {
        $tags = $this->tags ?? [];
        
        $this->tags = array_values(array_filter($tags, fn($t) => $t !== $tag));
    }
    
    public function hasTag(string $tag): bool
    {
        return in_array($tag, $this->tags ?? []);
    }
    
    public function updateScore(int $points): void
    {
        $this->lead_score = max(0, min(100, $this->lead_score + $points));
    }
    
    public function getTotalOpportunityValue(): float
    {
        return $this->opportunities()
                   ->whereIn('estagio', ['prospecting', 'qualification', 'proposal', 'negotiation'])
                   ->sum('valor');
    }
    
    public function getLastInteractionDate(): ?string
    {
        $lastInteraction = $this->interactions()
                               ->latest('interaction_date')
                               ->first();
        
        return $lastInteraction?->interaction_date;
    }
}