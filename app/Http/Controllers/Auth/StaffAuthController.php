<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Requests\Auth\StaffLoginRequest;
use Domain\Identity\Application\Commands\AuthenticateStaffCommand;
use Domain\Identity\Application\Commands\LogoutStaffCommand;
use Domain\Identity\Application\Handlers\AuthenticateStaffHandler;
use Domain\Identity\Application\Handlers\LogoutStaffHandler;
use Domain\Identity\Domain\Exceptions\InvalidCredentials;
use Domain\Identity\Domain\Exceptions\StaffAccountDeactivated;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class StaffAuthController
{
    public function __construct(
        private readonly AuthenticateStaffHandler $authenticate,
        private readonly LogoutStaffHandler $logout,
    ) {}

    public function create(): Response
    {
        return Inertia::render('Auth/StaffLogin');
    }

    public function store(StaffLoginRequest $request): RedirectResponse
    {
        try {
            $this->authenticate->handle(new AuthenticateStaffCommand(
                email: $request->string('email')->toString(),
                password: $request->string('password')->toString(),
                remember: $request->boolean('remember'),
            ));
        } catch (InvalidCredentials|StaffAccountDeactivated $exception) {
            // Deliberately attached to the `email` field either way — see
            // InvalidCredentials's doc comment on not disclosing which
            // part of the credential pair was wrong. A deactivated
            // account's message IS distinct (StaffAccountDeactivated),
            // which is an accepted, narrower disclosure than "wrong
            // password vs. unknown email".
            throw ValidationException::withMessages(['email' => $exception->getMessage()]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->logout->handle(new LogoutStaffCommand);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('staff.login');
    }
}
