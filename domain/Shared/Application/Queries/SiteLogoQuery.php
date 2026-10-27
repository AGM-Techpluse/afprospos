<?php

declare(strict_types=1);

namespace Domain\Shared\Application\Queries;

use Domain\Shared\Domain\Contracts\SiteSettings;

/** The admin's uploaded logo over the built-in default asset, so a shop that never uploaded one keeps shipping with the original branding. */
final class SiteLogoQuery
{
    public const KEY = 'appearance.site_logo';

    public function __construct(private readonly SiteSettings $settings) {}

    /** The stored path is relative to the public webroot (public/uploads/site/...), written there directly rather than via the storage:link symlink — see SiteAppearanceController::updateLogo(). */
    public function url(): string
    {
        $saved = (array) $this->settings->get(self::KEY, []);
        $path = $saved['path'] ?? null;

        return is_string($path) && $path !== '' ? asset($path) : asset('logo.jpg');
    }

    /** The stored path itself (not the URL) — used by the Handler to delete the previous file when it's replaced. */
    public function storedPath(): ?string
    {
        $saved = (array) $this->settings->get(self::KEY, []);
        $path = $saved['path'] ?? null;

        return is_string($path) && $path !== '' ? $path : null;
    }
}
