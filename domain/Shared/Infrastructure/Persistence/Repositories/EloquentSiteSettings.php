<?php

declare(strict_types=1);

namespace Domain\Shared\Infrastructure\Persistence\Repositories;

use Domain\Shared\Domain\Contracts\SiteSettings;
use Domain\Shared\Infrastructure\Persistence\Eloquent\SiteSettingRecord;

final class EloquentSiteSettings implements SiteSettings
{
    public function get(string $key, mixed $default = null): mixed
    {
        $record = SiteSettingRecord::query()->find($key);

        return $record !== null ? $record->value : $default;
    }

    public function put(string $key, mixed $value, int $updatedByStaffId): void
    {
        SiteSettingRecord::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'updated_by_staff_id' => $updatedByStaffId],
        );
    }

    public function forget(string $key): void
    {
        SiteSettingRecord::query()->whereKey($key)->delete();
    }
}
