<?php

declare(strict_types=1);

namespace Domain\Shared\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Shared\Application\Commands\UpdateWelcomePaletteCommand;
use Domain\Shared\Application\Queries\WelcomePaletteQuery;
use Domain\Shared\Domain\Contracts\SiteSettings;
use Domain\Shared\Domain\ValueObjects\StaffId;

final class UpdateWelcomePaletteHandler
{
    public function __construct(
        private readonly SiteSettings $settings,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(UpdateWelcomePaletteCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $before = $this->settings->get(WelcomePaletteQuery::KEY);

            if ($command->palette === null) {
                $this->settings->forget(WelcomePaletteQuery::KEY);
            } else {
                // Only the known tokens are stored; the request has already checked they are valid hex colours.
                $known = array_intersect_key($command->palette, WelcomePaletteQuery::defaults());
                $this->settings->put(WelcomePaletteQuery::KEY, array_map('strtoupper', $known), $command->updatedByStaffId);
            }

            $this->audit->record(
                module: 'Shared',
                eventType: $command->palette === null ? 'WelcomePaletteReset' : 'WelcomePaletteUpdated',
                actorStaffId: new StaffId($command->updatedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'site_setting',
                subjectId: 0,
                beforeState: $before,
                afterState: $command->palette,
            );
        });
    }
}
