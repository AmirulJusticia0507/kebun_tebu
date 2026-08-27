<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Block extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'code',
        'name',
        'polygon',
        'hectare',
        'pic_user_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'hectare' => 'decimal:2',
            'is_active' => 'boolean',
            'polygon' => 'array', // GeoJSON
        ];
    }

    public function pic()
    {
        return $this->belongsTo(User::class, 'pic_user_id');
    }

    public function reports()
    {
        return $this->hasMany(Report::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['code', 'name', 'hectare', 'pic_user_id', 'is_active'])
            ->logOnlyDirty();
    }
}