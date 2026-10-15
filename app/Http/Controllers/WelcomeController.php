<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Domain\Shared\Application\Queries\WelcomePaletteQuery;
use Inertia\Inertia;
use Inertia\Response;

/** The public landing page. Its colours come from the admin's Site appearance settings. */
final class WelcomeController
{
    public function __construct(private readonly WelcomePaletteQuery $palette) {}

    public function show(): Response
    {
        return Inertia::render('Welcome', [
            'palette' => $this->palette->resolved(),
        ]);
    }
}
