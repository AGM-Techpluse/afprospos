<?php

declare(strict_types=1);

namespace Domain\Shared\Application\Queries;

use Domain\Shared\Domain\Contracts\SiteSettings;
use Illuminate\Support\Facades\Storage;

/** The admin's uploaded logo over the built-in default asset, so a shop that never uploaded one keeps shipping with the original branding. */
final class SiteLogoQuery
{
    public const KEY = 'appearance.site_logo';

    public function __construct(private readonly SiteSettings $settings) {}

    public function url(): string
    {
        $saved = (array) $this->settings->get(self::KEY, []);
        $path = $saved['path'] ?? null;

        return is_string($path) && $path !== '' ? Storage::disk('public')->url($path) : asset('logo.jpg');
    }

    /** The stored path itself (not the URL) — used by the Handler to delete the previous file when it's replaced. */
    public function storedPath(): ?string
    {
        $saved = (array) $this->settings->get(self::KEY, []);
        $path = $saved['path'] ?? null;

        return is_string($path) && $path !== '' ? $path : null;
    }
}
