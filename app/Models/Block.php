<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

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

    /** @return BelongsTo<User, $this> */
    public function pic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pic_user_id');
    }

    /** @return HasMany<Report, $this> */
    public function reports(): HasMany
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
