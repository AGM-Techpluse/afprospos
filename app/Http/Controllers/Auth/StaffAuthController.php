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
        return Inertia::render('Admin/Auth/Login');
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
            // Deliberately a page-level `auth` error bag key, not `email` —
            // UI/UX §14C.1 specifies a general banner above the form, not a
            // field-attached message, precisely so the login failure gives
            // no visual hint about which half of the credential pair was
            // wrong. See InvalidCredentials's doc comment for the same
            // reasoning on the copy itself. A deactivated account's message
            // IS distinct (StaffAccountDeactivated), which is an accepted,
            // narrower disclosure than "wrong password vs. unknown email".
            throw ValidationException::withMessages(['auth' => $exception->getMessage()]);
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
