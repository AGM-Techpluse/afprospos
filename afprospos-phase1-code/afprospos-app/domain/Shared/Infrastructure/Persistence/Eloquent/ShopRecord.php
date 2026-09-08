<?php

declare(strict_types=1);

namespace Domain\Shared\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent persistence detail for the Shared Kernel `shops` table
 * (DBDD §9.1). Business behaviour belongs on the Shop module's domain
 * entity (Domain\Shop\Domain\Entities\Shop), never here (CPNC §3.4).
 *
 * Lives under Shared (not the Shop module's own Infrastructure folder)
 * because `shops` is a Shared Kernel table referenced by ID from many
 * bounded contexts (ADD §6.1); the Shop module owns the *behaviour*
 * around it via ShopRepository, not the raw row.
 *
 * @property int $id
 * @property string $name
 * @property string $sku_prefix_code
 * @property string $address
 * @property string $contact_phone
 * @property string $contact_email
 * @property array<string, mixed> $offline_policy
 * @property string $status
 */
final class ShopRecord extends Model
{
    use HasFactory;

    protected $table = 'shops';

    protected $fillable = [
        'name',
        'sku_prefix_code',
        'address',
        'contact_phone',
        'contact_email',
        'offline_policy',
        'status',
    ];

    protected $casts = [
        'offline_policy' => 'array',
    ];
}
