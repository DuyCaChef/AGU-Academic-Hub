<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdatePasswordRequest;
use App\Services\Auth\Actions\UpdateUserPasswordAction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

// Controller điều phối cập nhật mật khẩu người dùng
class PasswordController extends Controller
{
    // Cập nhật mật khẩu người dùng
    public function update(UpdatePasswordRequest $request, UpdateUserPasswordAction $action): RedirectResponse
    {
        $validated = $request->validated();

        $action->execute($request->user(), $validated['password']);

        return back();
    }
}

