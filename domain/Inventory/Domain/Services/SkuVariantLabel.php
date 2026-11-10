<?php

declare(strict_types=1);

namespace Domain\Inventory\Domain\Services;

final class SkuVariantLabel
{
    /** @param array<string, mixed> $attributes */
    public static function fromAttributes(array $attributes): string
    {
        $parts = [];

        foreach (['brand', 'color', 'storage', 'material', 'compatibility'] as $key) {
            if (! empty($attributes[$key])) {
                $parts[] = (string) $attributes[$key];
            }
        }

        foreach ((array) ($attributes['custom'] ?? []) as $value) {
            if ($value !== null && $value !== '') {
                $parts[] = (string) $value;
            }
        }

        return implode(' · ', $parts);
    }
}
