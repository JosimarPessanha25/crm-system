<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Ramsey\Uuid\Uuid;

/**
 * @property string $id
 * @property string $tipo
 * @property ?string $assunto
 * @property ?string $conteudo
 * @property ?string $direction
 * @property ?array $anexos
 * @property string $relacionado_tipo
 * @property string $relacionado_id
 * @property string $author_id
 * @property string $interaction_date
 * @property string $created_at
 */
class Interaction extends Model
{
    protected $table = 'interacoes';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false; // Only has created_at
    
    protected $fillable = [
        'tipo',
        'assunto',
        'conteudo',
        'direction',
        'anexos',
        'relacionado_tipo',
        'relacionado_id',
        'author_id',
        'interaction_date'
    ];
    
    protected $casts = [
        'anexos' => 'array',
        'interaction_date' => 'datetime',
        'created_at' => 'datetime'
    ];
    
    public const TIPO_EMAIL = 'email';
    public const TIPO_CALL = 'call';
    public const TIPO_MEETING = 'meeting';
    public const TIPO_NOTE = 'note';
    public const TIPO_SMS = 'sms';
    public const TIPO_WHATSAPP = 'whatsapp';
    
    public const TIPOS = [
        self::TIPO_EMAIL => 'E-mail',
        self::TIPO_CALL => 'Ligação',
        self::TIPO_MEETING => 'Reunião',
        self::TIPO_NOTE => 'Anotação',
        self::TIPO_SMS => 'SMS',
        self::TIPO_WHATSAPP => 'WhatsApp'
    ];
    
    public const DIRECTION_INBOUND = 'inbound';
    public const DIRECTION_OUTBOUND = 'outbound';
    
    public const DIRECTIONS = [
        self::DIRECTION_INBOUND => 'Entrada',
        self::DIRECTION_OUTBOUND => 'Saída'
    ];
    
    protected static function boot(): void
    {
        parent::boot();
        
        static::creating(function (self $model): void {
            if (!$model->getKey()) {
                $model->{$model->getKeyName()} = Uuid::uuid4()->toString();
            }
            
            if (!$model->interaction_date) {
                $model->interaction_date = now();
            }
        });
    }
    
    // Relationships
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
    
    // Polymorphic relationship
    public function relacionado()
    {
        return $this->morphTo('relacionado', 'relacionado_tipo', 'relacionado_id');
    }
    
    // Scopes
    public function scopeByAuthor($query, string $authorId)
    {
        return $query->where('author_id', $authorId);
    }
    
    public function scopeByType($query, string $type)
    {
        return $query->where('tipo', $type);
    }
    
    public function scopeByDirection($query, string $direction)
    {
        return $query->where('direction', $direction);
    }
    
    public function scopeRelatedTo($query, string $type, string $id)
    {
        return $query->where('relacionado_tipo', $type)
                    ->where('relacionado_id', $id);
    }
    
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('interaction_date', '>=', now()->subDays($days));
    }
    
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('assunto', 'LIKE', "%{$search}%")
              ->orWhere('conteudo', 'LIKE', "%{$search}%");
        });
    }
    
    // Accessors
    public function getTipoLabelAttribute(): string
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo;
    }
    
    public function getDirectionLabelAttribute(): ?string
    {
        return $this->direction ? (self::DIRECTIONS[$this->direction] ?? $this->direction) : null;
    }
    
    public function getTipoIconAttribute(): string
    {
        return match ($this->tipo) {
            self::TIPO_EMAIL => 'envelope',
            self::TIPO_CALL => 'telephone',
            self::TIPO_MEETING => 'people',
            self::TIPO_NOTE => 'sticky',
            self::TIPO_SMS => 'chat-dots',
            self::TIPO_WHATSAPP => 'whatsapp',
            default => 'chat'
        };
    }
    
    public function getDirectionColorAttribute(): string
    {
        return match ($this->direction) {
            self::DIRECTION_INBOUND => 'success',
            self::DIRECTION_OUTBOUND => 'primary',
            default => 'secondary'
        };
    }
    
    public function getFormattedDateAttribute(): string
    {
        $date = $this->interaction_date;
        $now = now();
        
        if ($date->isToday()) {
            return 'Hoje às ' . $date->format('H:i');
        }
        
        if ($date->isYesterday()) {
            return 'Ontem às ' . $date->format('H:i');
        }
        
        if ($date->diffInDays($now) <= 7) {
            return $date->format('l \à\s H:i');
        }
        
        return $date->format('d/m/Y \à\s H:i');
    }
    
    // Helper methods
    public function hasAttachments(): bool
    {
        return !empty($this->anexos);
    }
    
    public function getAttachmentCount(): int
    {
        return count($this->anexos ?? []);
    }
}