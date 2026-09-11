<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Requests\Auth\CustomerLoginRequest;
use App\Http\Requests\Auth\CustomerRegisterRequest;
use Domain\Identity\Application\Commands\AuthenticateCustomerCommand;
use Domain\Identity\Application\Commands\LogoutCustomerCommand;
use Domain\Identity\Application\Commands\RegisterCustomerCommand;
use Domain\Identity\Application\Handlers\AuthenticateCustomerHandler;
use Domain\Identity\Application\Handlers\LogoutCustomerHandler;
use Domain\Identity\Application\Handlers\RegisterCustomerHandler;
use Domain\Identity\Domain\Exceptions\DuplicateCustomerPhone;
use Domain\Identity\Domain\Exceptions\InvalidCredentials;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class CustomerAuthController
{
    public function __construct(
        private readonly AuthenticateCustomerHandler $authenticate,
        private readonly RegisterCustomerHandler $register,
        private readonly LogoutCustomerHandler $logout,
    ) {}

    public function createLogin(): Response
    {
        return Inertia::render('Customer/Auth/Login');
    }

    public function storeLogin(CustomerLoginRequest $request): RedirectResponse
    {
        try {
            $this->authenticate->handle(new AuthenticateCustomerCommand(
                email: $request->string('email')->toString(),
                password: $request->string('password')->toString(),
                remember: $request->boolean('remember'),
            ));
        } catch (InvalidCredentials $exception) {
            // Page-level `auth` error bag key, not `email` — UI/UX §14C.2
            // reuses §14C.1's general-banner pattern rather than a
            // field-attached message, so failure gives no hint about which
            // half of the credential pair was wrong.
            throw ValidationException::withMessages(['auth' => $exception->getMessage()]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('customer.dashboard'));
    }

    public function createRegister(): Response
    {
        return Inertia::render('Customer/Auth/Register');
    }

    public function storeRegister(CustomerRegisterRequest $request): RedirectResponse
    {
        try {
            $this->register->handle(new RegisterCustomerCommand(
                name: $request->string('name')->toString(),
                phone: $request->string('phone')->toString(),
                email: $request->string('email')->toString() ?: null,
                password: $request->string('password')->toString(),
            ));
        } catch (DuplicateCustomerPhone $exception) {
            throw ValidationException::withMessages(['phone' => $exception->getMessage()]);
        }

        $request->session()->regenerate();

        return redirect()->route('customer.dashboard');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->logout->handle(new LogoutCustomerCommand);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('customer.login');
    }
}
