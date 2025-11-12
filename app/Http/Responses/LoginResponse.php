<?php

namespace App\Http\Responses;

use App\Enums\User\UserRolesEnum;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     */
    public function toResponse($request): RedirectResponse
    {
        $user = $request->user();

        // Redirect Super Admin to admin dashboard
        if ($user->hasRole(UserRolesEnum::SuperAdmin->value)) {
            return redirect()->intended(route('admin.dashboard'));
        }

        // Redirect Editor to editor dashboard
        if ($user->hasRole(UserRolesEnum::ChurchAdmin->value)) {
            return redirect()->intended(route('admin.church.dashboard'));
        }

        // Default redirect for users without specific roles
        return redirect()->intended(route('admin.dashboard'));
    }
}
