<?php

declare(strict_types=1);

namespace Domain\Identity\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * Eloquent model for the `staff` table.
 *
 * This is an Infrastructure persistence detail, not a domain entity.
 * Named with the Record suffix per CPNC §3.4.
 *
 * Implements Authenticatable for Laravel auth guards.
 * Uses HasRoles for spatie/laravel-permission RBAC.
 */
final class StaffRecord extends Authenticatable
{
    use HasFactory;
    use HasRoles;
    use Notifiable;

    protected $table = 'staff';

    protected $fillable = [
        'name',
        'phone',
        'email',
        'password',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isDeactivated(): bool
    {
        return $this->status === 'deactivated';
    }

    /**
     * Shops this staff member is assigned to.
     */
    public function shops(): BelongsToMany
    {
        return $this->belongsToMany(
            ShopRecord::class,
            'staff_shop_assignments',
            'staff_id',
            'shop_id',
        )->withPivot('is_primary')->withTimestamps();
    }
}
