<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Report extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'user_id',
        'category_id',
        'block_id',
        'title',
        'description',
        'latitude',
        'longitude',
        'block_code',
        'photo_url',
        'status',
        'admin_note',
        'handled_by',
        'reported_at',
        'voice_note_url',
        'checklist_answers',
        'sla_deadline',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'reported_at' => 'datetime',
            'resolved_at' => 'datetime',
            'sla_deadline' => 'datetime',
            'checklist_answers' => 'array',
            'deleted_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function block()
    {
        return $this->belongsTo(Block::class);
    }

    public function handler()
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'status', 'admin_note', 'category_id', 'block_id'])
            ->logOnlyDirty();
    }

    public function isOpen(): bool
    {
        return $this->status === 'OPEN';
    }

    public function isOnProgress(): bool
    {
        return $this->status === 'ON_PROGRESS';
    }

    public function isClosed(): bool
    {
        return $this->status === 'CLOSED';
    }

    public function isOverdue(): bool
    {
        if ($this->isClosed() || !$this->sla_deadline) {
            return false;
        }
        return now()->greaterThan($this->sla_deadline);
    }
}