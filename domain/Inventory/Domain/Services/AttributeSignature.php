<?php

declare(strict_types=1);

namespace Domain\Inventory\Domain\Services;

/**
 * A canonical, order-independent fingerprint of a SKU's attributes. Two
 * configurations are the same SKU when their signatures match — so
 * "Color: Black" + "storage: 256GB" from the form and a CSV row with
 * "color=black, storage=256gb" resolve to one product configuration.
 * Stored on inventory_skus.attributes_signature so lookups are an indexed
 * equality, not a PHP-side scan.
 */
final class AttributeSignature
{
    /** @param  array<string, mixed>  $attributes */
    public static function of(array $attributes): string
    {
        $canonical = self::canonicalize($attributes);

        return hash('sha256', json_encode($canonical, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    /**
     * @param  array<mixed>  $value
     * @return array<mixed>
     */
    private static function canonicalize(array $value): array
    {
        $result = [];

        foreach ($value as $key => $item) {
            $normalizedKey = is_string($key) ? mb_strtolower(trim($key)) : $key;
            $result[$normalizedKey] = is_array($item)
                ? self::canonicalize($item)
                : mb_strtolower(trim((string) $item));
        }

        // Empty values are not part of the configuration: "storage: ''" is the same SKU as no storage at all.
        $result = array_filter($result, static fn ($item): bool => $item !== '' && $item !== []);

        ksort($result);

        return $result;
    }
}
