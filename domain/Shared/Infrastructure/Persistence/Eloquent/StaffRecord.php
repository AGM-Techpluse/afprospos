<?php

declare(strict_types=1);

namespace Domain\Shared\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Database\Factories\StaffRecordFactory;

/**
 * Eloquent persistence detail AND the Authenticatable model backing the
 * `staff` auth guard (config/auth.php). Also the model Spatie's
 * `HasRoles`/`HasPermissions` polymorphic tables (model_has_roles,
 * model_has_permissions) point at, per DBDD §10.
 *
 * `guard_name` is fixed to 'staff' so Spatie resolves roles/permissions
 * scoped to the staff guard specifically — this matters once a second
 * guard (customer) exists in the same application, even though customers
 * never actually hold roles.
 *
 * @property int $id
 * @property string $name
 * @property string $phone
 * @property string $email
 * @property string $status
 * @property string $password
 */
final class StaffRecord extends Authenticatable
{
    use HasFactory;
    use HasRoles;
    use Notifiable;

    protected string $guard_name = 'staff';

    protected $table = 'staff';

    protected $fillable = [
        'name',
        'phone',
        'email',
        'status',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    protected static function newFactory()
    {
        return StaffRecordFactory::new();
    }
}
