<?php

declare(strict_types=1);

namespace Domain\Shared\Infrastructure\Persistence\Eloquent;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

/**
 * Eloquent persistence detail AND the Authenticatable model backing the
 * `customer` auth guard (config/auth.php). Per BRD RBAC-07, customers have
 * no roles/permissions, so — unlike StaffRecord — this class does NOT use
 * Spatie's HasRoles trait.
 *
 * @property int $id
 * @property string $name
 * @property string $phone
 * @property ?string $email
 * @property array<string, mixed> $notification_preferences
 * @property bool $marketing_opt_out
 * @property string $status
 * @property string $password
 */
final class CustomerRecord extends Model implements AuthenticatableContract
{
    use Authenticatable;
    use HasFactory;
    use Notifiable;

    protected $table = 'customers';

    protected $fillable = [
        'name',
        'phone',
        'email',
        'notification_preferences',
        'marketing_opt_out',
        'status',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'notification_preferences' => 'array',
        'marketing_opt_out' => 'boolean',
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
