<?php

declare(strict_types=1);

namespace Domain\Inventory\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class InventoryMasterEntryRecord extends Model
{
    protected $table = 'inventory_master_entries';

    protected $fillable = ['kind', 'name', 'contact_phone', 'contact_email'];
}
