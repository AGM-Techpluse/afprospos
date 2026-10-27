<?php

declare(strict_types=1);

namespace Domain\Shared\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Shared\Application\Commands\UpdateSiteLogoCommand;
use Domain\Shared\Application\Queries\SiteLogoQuery;
use Domain\Shared\Domain\Contracts\SiteSettings;
use Domain\Shared\Domain\ValueObjects\StaffId;
use Illuminate\Support\Facades\File;

final class UpdateSiteLogoHandler
{
    public function __construct(
        private readonly SiteSettings $settings,
        private readonly SiteLogoQuery $logo,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(UpdateSiteLogoCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $previousPath = $this->logo->storedPath();

            if ($command->logoPath === null) {
                $this->settings->forget(SiteLogoQuery::KEY);
            } else {
                $this->settings->put(SiteLogoQuery::KEY, ['path' => $command->logoPath], $command->updatedByStaffId);
            }

            // The uploaded file itself is stored by the controller before this Command is built (directly under
            // public/, not storage/app/public — see SiteAppearanceController::updateLogo()) — once the new setting
            // is saved, the previous upload (if any) is no longer referenced from anywhere, so it's removed here
            // rather than accumulating orphaned files every time the logo changes.
            if ($previousPath !== null && $previousPath !== $command->logoPath) {
                File::delete(public_path($previousPath));
            }

            $this->audit->record(
                module: 'Shared',
                eventType: $command->logoPath === null ? 'SiteLogoReset' : 'SiteLogoUpdated',
                actorStaffId: new StaffId($command->updatedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'site_setting',
                subjectId: 0,
                beforeState: $previousPath !== null ? ['path' => $previousPath] : null,
                afterState: $command->logoPath !== null ? ['path' => $command->logoPath] : null,
            );
        });
    }
}
