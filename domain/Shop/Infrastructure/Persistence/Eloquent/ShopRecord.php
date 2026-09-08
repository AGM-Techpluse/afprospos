<?php

declare(strict_types=1);

namespace Domain\Shop\Infrastructure\Persistence\Eloquent;

use Domain\Identity\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Database\Factories\ShopRecordFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Eloquent model for the `shops` table.
 *
 * This is an Infrastructure persistence detail, not a domain entity.
 * Named with the Record suffix per CPNC §3.4.
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

    protected function casts(): array
    {
        return [
            'offline_policy' => 'array',
        ];
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    protected static function newFactory()
    {
        return ShopRecordFactory::new();
    }

    /**
     * Staff members assigned to this shop.
     */
    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(
            StaffRecord::class,
            'staff_shop_assignments',
            'shop_id',
            'staff_id',
        )->withPivot('is_primary')->withTimestamps();
    }
}
