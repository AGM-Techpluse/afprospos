<?php

declare(strict_types=1);

namespace Domain\Identity\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Eloquent model for the `customers` table.
 *
 * This is an Infrastructure persistence detail, not a domain entity.
 * Named with the Record suffix per CPNC §3.4.
 *
 * Implements Authenticatable for the customer auth guard.
 */
final class CustomerRecord extends Authenticatable
{
    use HasFactory;
    use Notifiable;

    protected $table = 'customers';

    protected $fillable = [
        'name',
        'phone',
        'email',
        'password',
        'notification_preferences',
        'marketing_opt_out',
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
            'notification_preferences' => 'array',
            'marketing_opt_out' => 'boolean',
        ];
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
