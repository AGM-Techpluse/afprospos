<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\FlashesToast;
use App\Http\Requests\Admin\ChangeStaffPasswordRequest;
use Domain\Identity\Application\Commands\ChangeStaffPasswordCommand;
use Domain\Identity\Application\Handlers\ChangeStaffPasswordHandler;
use Domain\Identity\Domain\Exceptions\InvalidCredentials;
use Domain\Shared\Application\DTOs\ActorContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

/** Self-service "my own account" actions — distinct from StaffController, which is an admin managing a DIFFERENT staff member's record under RBAC. */
final class AccountController
{
    use FlashesToast;

    public function __construct(
        private readonly ChangeStaffPasswordHandler $changePassword,
    ) {}

    public function updatePassword(ChangeStaffPasswordRequest $request): RedirectResponse
    {
        $actor = app(ActorContext::class);

        try {
            $this->changePassword->handle(new ChangeStaffPasswordCommand(
                staffId: $actor->staffId->value,
                currentPassword: $request->string('current_password')->toString(),
                newPassword: $request->string('password')->toString(),
            ));
        } catch (InvalidCredentials) {
            throw ValidationException::withMessages(['current_password' => 'Your current password is incorrect.']);
        }

        $this->flashSuccess('Password updated');

        return redirect()->back();
    }
}
