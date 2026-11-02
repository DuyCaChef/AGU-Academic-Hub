<?php

namespace App\Modules\Identity\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Application\Actions\DeleteUserAccountAction;
use App\Modules\Identity\Application\Actions\UpdateUserProfileAction;
use App\Modules\Identity\Application\Queries\GetUserProfileQuery;
use App\Modules\Identity\Presentation\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

// Controller điều phối luồng xem và cập nhật thông tin hồ sơ cá nhân
class ProfileController extends Controller
{
    // Hiển thị biểu mẫu chỉnh sửa hồ sơ cá nhân
    public function edit(Request $request, GetUserProfileQuery $query): Response
    {
        return Inertia::render('Profile/Edit', $query->execute($request->user()));
    }

    // Cập nhật thông tin hồ sơ cá nhân
    public function update(ProfileUpdateRequest $request, UpdateUserProfileAction $action): RedirectResponse
    {
        $action->execute($request->user(), $request->validated());

        return Redirect::route('profile.edit');
    }

    // Xóa vĩnh viễn tài khoản người dùng
    public function destroy(Request $request, DeleteUserAccountAction $action): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $action->execute($request, $request->user());

        return Redirect::to('/');
    }
}

