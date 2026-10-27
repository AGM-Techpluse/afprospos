<?php

declare(strict_types=1);

namespace Domain\Shared\Application\Queries;

use Domain\Shared\Domain\Contracts\SiteSettings;

/** The admin's chosen accent colour over the built-in default, so a shop that never changed it keeps the original "admin blue". */
final class AdminThemeQuery
{
    public const KEY = 'appearance.admin_theme';

    private const HEX = '/^#[0-9A-Fa-f]{6}$/';

    public function __construct(private readonly SiteSettings $settings) {}

    public function resolved(): string
    {
        $saved = (array) $this->settings->get(self::KEY, []);
        $accent = $saved['accent'] ?? null;

        return is_string($accent) && preg_match(self::HEX, $accent) === 1 ? strtoupper($accent) : self::defaults();
    }

    public static function defaults(): string
    {
        return (string) config('afprospos.appearance.admin_theme_accent');
    }
}
