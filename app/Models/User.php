<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes {
        HasRoles::assignRole as protected originalAssignRole;
        HasRoles::syncRoles as protected originalSyncRoles;
    }

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone_number',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected static function booted(): void
    {
        // Kolom `role` menjadi sumber kebenaran untuk authorisasi; Spatie roles
        // selalu dicerminkan dari kolom tersebut setiap kali user disimpan.
        static::saved(function (User $user): void {
            $role = $user->getAttribute('role');
            if (! is_string($role) || $role === '') {
                return;
            }

            if ($user->wasChanged('role') || ! $user->roles()->exists()) {
                $user->syncRoleFromColumn();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'created_at' => 'datetime',
        ];
    }

    /** @return HasMany<Report, $this> */
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    /** @return HasMany<Report, $this> */
    public function handledReports(): HasMany
    {
        return $this->hasMany(Report::class, 'handled_by');
    }

    /** @return HasMany<Block, $this> */
    public function assignedBlocks(): HasMany
    {
        return $this->hasMany(Block::class, 'pic_user_id');
    }

    /** @return HasMany<PushSubscription, $this> */
    public function pushSubscriptions(): HasMany
    {
        return $this->hasMany(PushSubscription::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isFieldOfficer(): bool
    {
        return $this->role === 'field_officer';
    }

    /**
     * Assign role Spatie. Aplikasi hanya mendukung satu role per user, sehingga
     * role lama diganti oleh role yang baru ditambahkan dan kolom `role` ikut
     * dicerminkan.
     *
     * @param  mixed  ...$roles
     */
    public function assignRole(...$roles): static
    {
        $before = $this->exists ? $this->getRoleNames()->all() : [];
        $result = $this->originalAssignRole(...$roles);

        if ($this->exists) {
            $after = $this->getRoleNames()->all();
            $added = array_values(array_diff($after, $before));

            if (count($after) > 1) {
                $winner = $added === [] ? ($after[0] ?? null) : end($added);
                if ($winner) {
                    $this->originalSyncRoles([$winner]);
                    $this->unsetRelation('roles');
                    $after = [$winner];
                }
            }

            if (count($after) === 1 && $after[0] !== $this->role) {
                $this->role = $after[0];
                $this->saveQuietly();
            }
        }

        return $result;
    }

    /**
     * Sinkronkan role Spatie, lalu cerminkan ke kolom `role`.
     *
     * @param  mixed  ...$roles
     */
    public function syncRoles(...$roles): static
    {
        $result = $this->originalSyncRoles(...$roles);
        $this->reflectSpatieRolesToColumn();

        return $result;
    }

    /**
     * Jadikan kolom `role` sebagai sumber Spatie roles.
     */
    public function syncRoleFromColumn(): void
    {
        $role = $this->getAttribute('role');
        if (! is_string($role) || $role === '') {
            return;
        }

        $spatieRole = Role::findOrCreate($role, config('auth.defaults.guard', 'web'));
        $this->originalSyncRoles([$spatieRole]);
        $this->setRelation('roles', collect([$spatieRole]));
    }

    /**
     * Cerminkan Spatie roles pertama ke kolom `role` (tanpa event, tanpa rekursi).
     */
    protected function reflectSpatieRolesToColumn(): void
    {
        if (! $this->exists) {
            return;
        }

        $current = $this->getRoleNames()->first();
        if ($current && $current !== $this->role) {
            $this->role = $current;
            $this->saveQuietly();
        }
    }
}
