<?php

namespace App\Modules\Identity\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Application\Actions\RegisterStudentAction;
use App\Modules\Identity\Presentation\Http\Requests\RegisterUserRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

// Controller điều phối luồng đăng ký tài khoản sinh viên mới
class RegisteredUserController extends Controller
{
    // Hiển thị biểu mẫu đăng ký
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    // Xử lý yêu cầu đăng ký tài khoản sinh viên
    public function store(RegisterUserRequest $request, RegisterStudentAction $action): RedirectResponse
    {
        $action->execute($request->validated());

        return redirect(route('dashboard', absolute: false));
    }
}

