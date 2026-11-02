<?php

namespace App\Modules\Identity\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Application\Actions\UpdateUserPasswordAction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

// Controller điều phối cập nhật mật khẩu người dùng
class PasswordController extends Controller
{
    // Cập nhật mật khẩu người dùng
    public function update(Request $request, UpdateUserPasswordAction $action): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', Password::defaults(), 'confirmed'],
        ]);

        $action->execute($request->user(), $validated['password']);

        return back();
    }
}

