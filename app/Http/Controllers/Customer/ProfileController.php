<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Concerns\FlashesToast;
use App\Http\Requests\Customer\UpdateCustomerProfileRequest;
use App\Http\Requests\Customer\UploadCustomerAvatarRequest;
use Domain\Identity\Application\Commands\UpdateCustomerAvatarCommand;
use Domain\Identity\Application\Commands\UpdateCustomerProfileCommand;
use Domain\Identity\Application\Handlers\UpdateCustomerAvatarHandler;
use Domain\Identity\Application\Handlers\UpdateCustomerProfileHandler;
use Domain\Identity\Domain\Exceptions\DuplicateCustomerEmail;
use Domain\Identity\Domain\Exceptions\DuplicateCustomerPhone;
use Domain\Shared\Domain\ValueObjects\CustomerId;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class ProfileController
{
    use FlashesToast;

    public function __construct(
        private readonly UpdateCustomerProfileHandler $updateProfile,
        private readonly UpdateCustomerAvatarHandler $updateAvatar,
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

        $this->flashSuccess('Profile updated');

        return redirect()->route('customer.profile.show');
    }

    /** File handling is a Controller-level concern (CPNC §2.3) -- the Command carries only the already-stored path. Mirrors RepairsController::uploadPhoto. */
    public function uploadAvatar(UploadCustomerAvatarRequest $request): RedirectResponse
    {
        $customer = $request->user('customer');
        $previousPath = $customer->avatar_path;

        $path = $request->file('avatar')->store('customer-avatars', 'public');

        $this->updateAvatar->handle(new UpdateCustomerAvatarCommand(
            customerId: new CustomerId($customer->id),
            avatarPath: $path,
        ));

        if ($previousPath !== null) {
            Storage::disk('public')->delete($previousPath);
        }

        $this->flashSuccess('Profile photo updated');

        return redirect()->route('customer.profile.show');
    }

    public function removeAvatar(Request $request): RedirectResponse
    {
        $customer = $request->user('customer');
        $previousPath = $customer->avatar_path;

        $this->updateAvatar->handle(new UpdateCustomerAvatarCommand(
            customerId: new CustomerId($customer->id),
            avatarPath: null,
        ));

        if ($previousPath !== null) {
            Storage::disk('public')->delete($previousPath);
        }

        $this->flashSuccess('Profile photo removed');

        return redirect()->route('customer.profile.show');
    }
}
