<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ReportDraft extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'description',
        'latitude',
        'longitude',
        'block_code',
        'photo_paths',
        'voice_note_path',
        'checklist_data',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'photo_paths' => 'array',
            'checklist_data' => 'array',
            'synced_at' => 'datetime',
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

    public function isSynced(): bool
    {
        return !is_null($this->synced_at);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['user_id', 'category_id', 'synced_at'])
            ->logOnlyDirty();
    }
}