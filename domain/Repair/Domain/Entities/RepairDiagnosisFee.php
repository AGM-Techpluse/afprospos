<?php

declare(strict_types=1);

namespace Domain\Repair\Domain\Entities;

use Domain\Repair\Domain\Exceptions\InvalidDiagnosisFeeState;
use Domain\Repair\Domain\ValueObjects\RepairJobId;

/**
 * The diagnosis fee for one repair job. Frontdesk sets it after diagnosis;
 * managers may adjust it while authorizing the quote. A declined fee is a legacy
 * state kept for existing records and is not due.
 */
final class RepairDiagnosisFee
{
    private function __construct(
        private readonly RepairJobId $repairJobId,
        private int $amountMinor,
        private string $decision,
        private readonly int $setByStaffId,
        private ?int $decidedByStaffId,
    ) {}

    public static function set(RepairJobId $repairJobId, int $amountMinor, int $setByStaffId): self
    {
        if ($amountMinor <= 0) {
            throw InvalidDiagnosisFeeState::forJob($repairJobId->value, 'The diagnosis fee must be more than zero.');
        }

        return new self($repairJobId, $amountMinor, 'approved', $setByStaffId, $setByStaffId);
    }

    public static function reconstitute(RepairJobId $repairJobId, int $amountMinor, string $decision, int $setByStaffId, ?int $decidedByStaffId): self
    {
        return new self($repairJobId, $amountMinor, $decision, $setByStaffId, $decidedByStaffId);
    }

    /** Re-setting is allowed unless a legacy declined decision made the fee not due. */
    public function changeAmount(int $amountMinor, int $setByStaffId): void
    {
        if ($this->decision === 'declined') {
            throw InvalidDiagnosisFeeState::forJob($this->repairJobId->value, 'The fee has already been '.$this->decision.'.');
        }

        if ($amountMinor <= 0) {
            throw InvalidDiagnosisFeeState::forJob($this->repairJobId->value, 'The diagnosis fee must be more than zero.');
        }

        $this->amountMinor = $amountMinor;
        $this->decision = 'approved';
        $this->decidedByStaffId = $setByStaffId;
    }

    public function approve(int $decidedByStaffId): void
    {
        $this->decide('approved', $decidedByStaffId);
    }

    public function decline(int $decidedByStaffId): void
    {
        $this->decide('declined', $decidedByStaffId);
    }

    private function decide(string $decision, int $decidedByStaffId): void
    {
        if ($this->decision !== 'pending') {
            throw InvalidDiagnosisFeeState::forJob($this->repairJobId->value, 'The fee has already been '.$this->decision.'.');
        }

        $this->decision = $decision;
        $this->decidedByStaffId = $decidedByStaffId;
    }

    public function repairJobId(): RepairJobId
    {
        return $this->repairJobId;
    }

    public function amountMinor(): int
    {
        return $this->amountMinor;
    }

    public function decision(): string
    {
        return $this->decision;
    }

    public function setByStaffId(): int
    {
        return $this->setByStaffId;
    }

    public function decidedByStaffId(): ?int
    {
        return $this->decidedByStaffId;
    }
}
