<?php

declare(strict_types=1);

namespace Domain\Shared\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class SiteSettingRecord extends Model
{
    protected $table = 'site_settings';

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['key', 'value', 'updated_by_staff_id'];

    protected $casts = [
        'value' => 'array',
    ];
}
