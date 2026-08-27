<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Category extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'icon_marker',
        'color_code',
        'sla_hours',
        'checklist_template',
    ];

    protected function casts(): array
    {
        return [
            'checklist_template' => 'array',
            'sla_hours' => 'integer',
        ];
    }

    public function reports()
    {
        return $this->hasMany(Report::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'icon_marker', 'color_code', 'sla_hours'])
            ->logOnlyDirty();
    }
}