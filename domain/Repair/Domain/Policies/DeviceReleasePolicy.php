<?php

declare(strict_types=1);

namespace Domain\Repair\Domain\Policies;

/**
 * Implementation Plan Phase 6 exit criterion: "the device cannot be
 * released when payment is outstanding without an authorized, audited
 * override." The override itself is recorded as a Collection case event
 * before this is ever asked with hasAdministrativeOverride=true — see
 * RecordCollectionOverrideCommand.
 */
final class DeviceReleasePolicy
{
    public function canRelease(string $financialStatus, bool $hasAdministrativeOverride): bool
    {
        return $financialStatus === 'fully_paid' || $hasAdministrativeOverride;
    }
}
