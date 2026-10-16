<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Commands;

/**
 * Exactly one of `deviceTypeId`/`deviceModelId` is the caller's intent:
 * `deviceTypeId` creates a type-level/fallback tag (today's path,
 * unchanged); `deviceModelId` creates a model-specific override, and the
 * Handler resolves the owning type itself from the model rather than
 * trusting a client-submitted `deviceTypeId` alongside it.
 */
final readonly class CreateDeviceProblemTagCommand
{
    public function __construct(
        public ?int $deviceTypeId,
        public ?int $deviceModelId,
        public string $label,
        public int $sortOrder,
        public int $createdByStaffId,
        public ?int $commissionAmountMinor = null,
    ) {}
}
