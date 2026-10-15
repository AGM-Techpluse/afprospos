<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\FlashesToast;
use App\Http\Requests\Admin\UpdateWelcomePaletteRequest;
use Domain\Shared\Application\Commands\UpdateWelcomePaletteCommand;
use Domain\Shared\Application\DTOs\ActorContext;
use Domain\Shared\Application\Handlers\UpdateWelcomePaletteHandler;
use Domain\Shared\Application\Queries\WelcomePaletteQuery;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/** Settings → Site appearance: recolour the landing page without a code change. */
final class SiteAppearanceController
{
    use FlashesToast;

    public function __construct(
        private readonly WelcomePaletteQuery $palette,
        private readonly UpdateWelcomePaletteHandler $update,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Settings/Appearance/Index', [
            'palette' => $this->palette->resolved(),
            'defaults' => WelcomePaletteQuery::defaults(),
        ]);
    }

    public function update(UpdateWelcomePaletteRequest $request): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->update->handle(new UpdateWelcomePaletteCommand($request->palette(), $actor->staffId->value));

        $this->flashSuccess('Landing page colours saved');

        return redirect()->back();
    }

    public function reset(): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->update->handle(new UpdateWelcomePaletteCommand(null, $actor->staffId->value));

        $this->flashSuccess('Landing page colours reset to the original design');

        return redirect()->back();
    }
}
