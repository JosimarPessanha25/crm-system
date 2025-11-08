<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Ramsey\Uuid\Uuid;

/**
 * @property string $id
 * @property string $tipo
 * @property string $assunto
 * @property ?string $descricao
 * @property string $status
 * @property string $prioridade
 * @property ?string $due_date
 * @property ?string $completed_at
 * @property ?int $duracao_minutos
 * @property ?string $resultado
 * @property ?string $relacionado_tipo
 * @property ?string $relacionado_id
 * @property string $assigned_to
 * @property string $created_by
 * @property string $created_at
 * @property string $updated_at
 */
class Activity extends Model
{
    protected $table = 'atividades';
    protected $keyType = 'string';
    public $incrementing = false;
    
    protected $fillable = [
        'tipo',
        'assunto',
        'descricao',
        'status',
        'prioridade',
        'due_date',
        'completed_at',
        'duracao_minutos',
        'resultado',
        'relacionado_tipo',
        'relacionado_id',
        'assigned_to',
        'created_by'
    ];
    
    protected $casts = [
        'due_date' => 'datetime',
        'completed_at' => 'datetime',
        'duracao_minutos' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    public const TIPO_CALL = 'call';
    public const TIPO_EMAIL = 'email';
    public const TIPO_MEETING = 'meeting';
    public const TIPO_TASK = 'task';
    public const TIPO_DEMO = 'demo';
    public const TIPO_FOLLOW_UP = 'follow_up';
    
    public const TIPOS = [
        self::TIPO_CALL => 'Ligação',
        self::TIPO_EMAIL => 'E-mail',
        self::TIPO_MEETING => 'Reunião',
        self::TIPO_TASK => 'Tarefa',
        self::TIPO_DEMO => 'Demonstração',
        self::TIPO_FOLLOW_UP => 'Follow-up'
    ];
    
    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    
    public const STATUSES = [
        self::STATUS_PENDING => 'Pendente',
        self::STATUS_COMPLETED => 'Concluída',
        self::STATUS_CANCELLED => 'Cancelada'
    ];
    
    public const PRIORIDADE_BAIXA = 'baixa';
    public const PRIORIDADE_MEDIA = 'media';
    public const PRIORIDADE_ALTA = 'alta';
    public const PRIORIDADE_URGENTE = 'urgente';
    
    public const PRIORIDADES = [
        self::PRIORIDADE_BAIXA => 'Baixa',
        self::PRIORIDADE_MEDIA => 'Média',
        self::PRIORIDADE_ALTA => 'Alta',
        self::PRIORIDADE_URGENTE => 'Urgente'
    ];
    
    protected static function boot(): void
    {
        parent::boot();
        
        static::creating(function (self $model): void {
            if (!$model->getKey()) {
                $model->{$model->getKeyName()} = Uuid::uuid4()->toString();
            }
        });
        
        static::updating(function (self $model): void {
            // Set completion date when status changes to completed
            if ($model->isDirty('status') && $model->status === self::STATUS_COMPLETED && !$model->completed_at) {
                $model->completed_at = now();
            }
        });
    }
    
    // Relationships
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
    
    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    
    // Polymorphic relationships
    public function relacionado()
    {
        return $this->morphTo('relacionado', 'relacionado_tipo', 'relacionado_id');
    }
    
    // Scopes
    public function scopeByUser($query, string $userId)
    {
        return $query->where('assigned_to', $userId);
    }
    
    public function scopeByType($query, string $type)
    {
        return $query->where('tipo', $type);
    }
    
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
    
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }
    
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }
    
    public function scopeOverdue($query)
    {
        return $query->where('status', self::STATUS_PENDING)
                    ->where('due_date', '<', now());
    }
    
    public function scopeDueToday($query)
    {
        return $query->where('status', self::STATUS_PENDING)
                    ->whereDate('due_date', today());
    }
    
    public function scopeDueThisWeek($query)
    {
        return $query->where('status', self::STATUS_PENDING)
                    ->whereBetween('due_date', [now()->startOfWeek(), now()->endOfWeek()]);
    }
    
    public function scopeByPriority($query, string $priority)
    {
        return $query->where('prioridade', $priority);
    }
    
    public function scopeHighPriority($query)
    {
        return $query->whereIn('prioridade', [self::PRIORIDADE_ALTA, self::PRIORIDADE_URGENTE]);
    }
    
    public function scopeRelatedTo($query, string $type, string $id)
    {
        return $query->where('relacionado_tipo', $type)
                    ->where('relacionado_id', $id);
    }
    
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('assunto', 'LIKE', "%{$search}%")
              ->orWhere('descricao', 'LIKE', "%{$search}%");
        });
    }
    
    // Accessors
    public function getTipoLabelAttribute(): string
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo;
    }
    
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
    
    public function getPrioridadeLabelAttribute(): string
    {
        return self::PRIORIDADES[$this->prioridade] ?? $this->prioridade;
    }
    
    public function getStatusBadgeColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => $this->isOverdue() ? 'danger' : 'warning',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_CANCELLED => 'secondary',
            default => 'secondary'
        };
    }
    
    public function getPriorityBadgeColorAttribute(): string
    {
        return match ($this->prioridade) {
            self::PRIORIDADE_BAIXA => 'success',
            self::PRIORIDADE_MEDIA => 'info',
            self::PRIORIDADE_ALTA => 'warning',
            self::PRIORIDADE_URGENTE => 'danger',
            default => 'secondary'
        };
    }
    
    public function getFormattedDueDateAttribute(): ?string
    {
        if (!$this->due_date) {
            return null;
        }
        
        $date = $this->due_date;
        $now = now();
        
        if ($date->isToday()) {
            return 'Hoje às ' . $date->format('H:i');
        }
        
        if ($date->isTomorrow()) {
            return 'Amanhã às ' . $date->format('H:i');
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
    public function isOverdue(): bool
    {
        return $this->status === self::STATUS_PENDING && 
               $this->due_date && 
               $this->due_date->isPast();
    }
    
    public function isDueToday(): bool
    {
        return $this->due_date && $this->due_date->isToday();
    }
    
    public function complete(?string $resultado = null): void
    {
        $this->status = self::STATUS_COMPLETED;
        $this->completed_at = now();
        
        if ($resultado) {
            $this->resultado = $resultado;
        }
        
        $this->save();
    }
    
    public function cancel(?string $motivo = null): void
    {
        $this->status = self::STATUS_CANCELLED;
        
        if ($motivo) {
            $this->resultado = 'Cancelada: ' . $motivo;
        }
        
        $this->save();
    }
    
    public function reschedule(string $newDueDate): void
    {
        $this->due_date = $newDueDate;
        $this->save();
    }
    
    public function getDurationFormatted(): ?string
    {
        if (!$this->duracao_minutos) {
            return null;
        }
        
        $hours = intval($this->duracao_minutos / 60);
        $minutes = $this->duracao_minutos % 60;
        
        if ($hours > 0) {
            return $hours . 'h' . ($minutes > 0 ? ' ' . $minutes . 'min' : '');
        }
        
        return $minutes . 'min';
    }
}