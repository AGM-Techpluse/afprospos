<?php

declare(strict_types=1);

namespace Domain\Repair\Domain\Entities;

use Domain\Repair\Domain\ValueObjects\DeviceTypeId;

/** A repair-intake catalog entry (e.g. "Smartphones", "Drones") — admin-editable, replaces what used to be a hardcoded list in the frontend. */
final class DeviceType
{
    private function __construct(
        private readonly ?DeviceTypeId $id,
        private string $label,
        private string $icon,
        private int $sortOrder,
    ) {}

    public static function create(string $label, string $icon, int $sortOrder): self
    {
        return new self(null, $label, $icon, $sortOrder);
    }

    public static function reconstitute(DeviceTypeId $id, string $label, string $icon, int $sortOrder): self
    {
        return new self($id, $label, $icon, $sortOrder);
    }

    public function rename(string $label, string $icon): void
    {
        $this->label = $label;
        $this->icon = $icon;
    }

    public function reorder(int $sortOrder): void
    {
        $this->sortOrder = $sortOrder;
    }

    public function id(): ?DeviceTypeId
    {
        return $this->id;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function icon(): string
    {
        return $this->icon;
    }

    public function sortOrder(): int
    {
        return $this->sortOrder;
    }
}
