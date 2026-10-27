<?php

declare(strict_types=1);

namespace Domain\Shared\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Shared\Application\Commands\UpdateAdminThemeCommand;
use Domain\Shared\Application\Queries\AdminThemeQuery;
use Domain\Shared\Domain\Contracts\SiteSettings;
use Domain\Shared\Domain\ValueObjects\StaffId;

final class UpdateAdminThemeHandler
{
    public function __construct(
        private readonly SiteSettings $settings,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(UpdateAdminThemeCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            if ($command->accent === null) {
                $this->settings->forget(AdminThemeQuery::KEY);
            } else {
                $this->settings->put(AdminThemeQuery::KEY, ['accent' => strtoupper($command->accent)], $command->updatedByStaffId);
            }

            $this->audit->record(
                module: 'Shared',
                eventType: $command->accent === null ? 'AdminThemeReset' : 'AdminThemeUpdated',
                actorStaffId: new StaffId($command->updatedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'site_setting',
                subjectId: 0,
                beforeState: null,
                afterState: $command->accent !== null ? ['accent' => $command->accent] : null,
            );
        });
    }
}
