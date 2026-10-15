<?php

declare(strict_types=1);

namespace Domain\Shared\Application\Queries;

use Domain\Shared\Domain\Contracts\SiteSettings;

/** The landing page palette: the admin's saved colours over the built-in defaults, so a token the admin never touched keeps its original value. */
final class WelcomePaletteQuery
{
    public const KEY = 'appearance.welcome_palette';

    private const HEX = '/^#[0-9A-Fa-f]{6}$/';

    public function __construct(private readonly SiteSettings $settings) {}

    /** @return array<string, string> */
    public function resolved(): array
    {
        $defaults = (array) config('afprospos.appearance.welcome_palette');
        $saved = (array) $this->settings->get(self::KEY, []);

        $palette = $defaults;

        foreach ($defaults as $token => $default) {
            if (isset($saved[$token]) && is_string($saved[$token]) && preg_match(self::HEX, $saved[$token]) === 1) {
                $palette[$token] = strtoupper($saved[$token]);
            }
        }

        return $palette;
    }

    /** @return array<string, string> */
    public static function defaults(): array
    {
        return (array) config('afprospos.appearance.welcome_palette');
    }
}
