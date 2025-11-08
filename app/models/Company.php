<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Ramsey\Uuid\Uuid;

/**
 * @property string $id
 * @property string $nome
 * @property ?string $cnpj
 * @property ?array $endereco
 * @property ?string $setor
 * @property ?string $website
 * @property ?string $telefone
 * @property ?string $observacoes
 * @property ?string $owner_id
 * @property string $created_at
 * @property string $updated_at
 */
class Company extends Model
{
    protected $table = 'empresas';
    protected $keyType = 'string';
    public $incrementing = false;
    
    protected $fillable = [
        'nome',
        'cnpj',
        'endereco',
        'setor',
        'website',
        'telefone',
        'observacoes',
        'owner_id'
    ];
    
    protected $casts = [
        'endereco' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
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
    
    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class, 'empresa_id');
    }
    
    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class, 'empresa_id');
    }
    
    // Scopes
    public function scopeByOwner($query, string $ownerId)
    {
        return $query->where('owner_id', $ownerId);
    }
    
    public function scopeBySetor($query, string $setor)
    {
        return $query->where('setor', $setor);
    }
    
    public function scopeSearch($query, string $search)
    {
        return $query->where('nome', 'LIKE', "%{$search}%")
                    ->orWhere('cnpj', 'LIKE', "%{$search}%")
                    ->orWhere('setor', 'LIKE', "%{$search}%");
    }
    
    // Accessors
    public function getFormattedCnpjAttribute(): ?string
    {
        if (!$this->cnpj) {
            return null;
        }
        
        $cnpj = preg_replace('/\D/', '', $this->cnpj);
        
        if (strlen($cnpj) === 14) {
            return substr($cnpj, 0, 2) . '.' . 
                   substr($cnpj, 2, 3) . '.' . 
                   substr($cnpj, 5, 3) . '/' . 
                   substr($cnpj, 8, 4) . '-' . 
                   substr($cnpj, 12, 2);
        }
        
        return $this->cnpj;
    }
    
    public function getEnderecoCompletoAttribute(): ?string
    {
        if (!$this->endereco || !is_array($this->endereco)) {
            return null;
        }
        
        $partes = [];
        
        if (!empty($this->endereco['rua'])) {
            $partes[] = $this->endereco['rua'];
        }
        
        if (!empty($this->endereco['numero'])) {
            $partes[] = $this->endereco['numero'];
        }
        
        if (!empty($this->endereco['cidade'])) {
            $partes[] = $this->endereco['cidade'];
        }
        
        if (!empty($this->endereco['estado'])) {
            $partes[] = $this->endereco['estado'];
        }
        
        if (!empty($this->endereco['cep'])) {
            $partes[] = 'CEP: ' . $this->endereco['cep'];
        }
        
        return implode(', ', $partes);
    }
    
    // Helper methods
    public function getTotalOpportunityValue(): float
    {
        return $this->opportunities()
                   ->whereIn('estagio', ['prospecting', 'qualification', 'proposal', 'negotiation'])
                   ->sum('valor');
    }
    
    public function getContactsCount(): int
    {
        return $this->contacts()->count();
    }
    
    public function getOpportunitiesCount(): int
    {
        return $this->opportunities()->count();
    }
}