<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\FlashesToast;
use App\Http\Requests\Admin\UpdateSiteLogoRequest;
use App\Http\Requests\Admin\UpdateWelcomePaletteRequest;
use Domain\Shared\Application\Commands\UpdateSiteLogoCommand;
use Domain\Shared\Application\Commands\UpdateWelcomePaletteCommand;
use Domain\Shared\Application\DTOs\ActorContext;
use Domain\Shared\Application\Handlers\UpdateSiteLogoHandler;
use Domain\Shared\Application\Handlers\UpdateWelcomePaletteHandler;
use Domain\Shared\Application\Queries\SiteLogoQuery;
use Domain\Shared\Application\Queries\WelcomePaletteQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/** Settings → Site appearance: recolour the landing page and change the site logo without a code change or deploy. */
final class SiteAppearanceController
{
    use FlashesToast;

    public function __construct(
        private readonly WelcomePaletteQuery $palette,
        private readonly UpdateWelcomePaletteHandler $update,
        private readonly SiteLogoQuery $logo,
        private readonly UpdateSiteLogoHandler $updateLogo,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Settings/Appearance/Index', [
            'palette' => $this->palette->resolved(),
            'defaults' => WelcomePaletteQuery::defaults(),
            'logoUrl' => $this->logo->url(),
            'hasCustomLogo' => $this->logo->storedPath() !== null,
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

    public function updateLogo(UpdateSiteLogoRequest $request): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $path = $request->file('logo')->storeAs(
            'site',
            Str::uuid()->toString().'.'.$request->file('logo')->getClientOriginalExtension(),
            'public',
        );

        $this->updateLogo->handle(new UpdateSiteLogoCommand($path, $actor->staffId->value));

        $this->flashSuccess('Site logo updated');

        return redirect()->back();
    }

    public function resetLogo(): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->updateLogo->handle(new UpdateSiteLogoCommand(null, $actor->staffId->value));

        $this->flashSuccess('Site logo reset to the default');

        return redirect()->back();
    }
}
