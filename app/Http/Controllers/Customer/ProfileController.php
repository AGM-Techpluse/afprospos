<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Http\Requests\Customer\UpdateCustomerProfileRequest;
use Domain\Identity\Application\Commands\UpdateCustomerProfileCommand;
use Domain\Identity\Application\Handlers\UpdateCustomerProfileHandler;
use Domain\Identity\Domain\Exceptions\DuplicateCustomerEmail;
use Domain\Identity\Domain\Exceptions\DuplicateCustomerPhone;
use Domain\Shared\Domain\ValueObjects\CustomerId;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class ProfileController
{
    public function __construct(
        private readonly UpdateCustomerProfileHandler $updateProfile,
    ) {}

    public function show(): Response
    {
        return Inertia::render('Customer/Profile/Show');
    }

    public function update(UpdateCustomerProfileRequest $request): RedirectResponse
    {
        try {
            $this->updateProfile->handle(new UpdateCustomerProfileCommand(
                customerId: new CustomerId($request->user('customer')->id),
                name: $request->string('name')->toString(),
                phone: $request->string('phone')->toString(),
                email: $request->string('email')->toString() ?: null,
            ));
        } catch (DuplicateCustomerPhone $exception) {
            throw ValidationException::withMessages(['phone' => $exception->getMessage()]);
        } catch (DuplicateCustomerEmail $exception) {
            throw ValidationException::withMessages(['email' => $exception->getMessage()]);
        }

        return redirect()->route('customer.profile.show');
    }
}
