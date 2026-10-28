<?php

declare(strict_types=1);

namespace Domain\Shared\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Shared\Application\Commands\UpdateSiteFaviconCommand;
use Domain\Shared\Application\Queries\SiteFaviconQuery;
use Domain\Shared\Domain\Contracts\SiteSettings;
use Domain\Shared\Domain\ValueObjects\StaffId;
use Illuminate\Support\Facades\File;

final class UpdateSiteFaviconHandler
{
    public function __construct(
        private readonly SiteSettings $settings,
        private readonly SiteFaviconQuery $favicon,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(UpdateSiteFaviconCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $previousPath = $this->favicon->storedPath();

            if ($command->faviconPath === null) {
                $this->settings->forget(SiteFaviconQuery::KEY);
            } else {
                $this->settings->put(SiteFaviconQuery::KEY, ['path' => $command->faviconPath], $command->updatedByStaffId);
            }

            // The uploaded file itself is stored by the controller before this Command is built (directly under
            // public/, not storage/app/public — see SiteAppearanceController::updateFavicon()) — once the new
            // setting is saved, the previous upload (if any) is no longer referenced from anywhere, so it's
            // removed here rather than accumulating orphaned files every time the favicon changes.
            if ($previousPath !== null && $previousPath !== $command->faviconPath) {
                File::delete(public_path($previousPath));
            }

            $this->audit->record(
                module: 'Shared',
                eventType: $command->faviconPath === null ? 'SiteFaviconReset' : 'SiteFaviconUpdated',
                actorStaffId: new StaffId($command->updatedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'site_setting',
                subjectId: 0,
                beforeState: $previousPath !== null ? ['path' => $previousPath] : null,
                afterState: $command->faviconPath !== null ? ['path' => $command->faviconPath] : null,
            );
        });
    }
}
