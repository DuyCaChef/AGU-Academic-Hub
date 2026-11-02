<?php

namespace App\Modules\Identity\Presentation\Http\Requests;

use App\Modules\Identity\Application\Actions\AuthenticateUserAction;
use Illuminate\Foundation\Http\FormRequest;

// Form Request xác thực thông tin đăng nhập
class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'    => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required'    => 'Vui lòng nhập Email hoặc Mã số sinh viên.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
        ];
    }

    // Xác thực thông tin đăng nhập ủy quyền qua AuthenticateUserAction
    public function xacThucDangNhap(): void
    {
        app(AuthenticateUserAction::class)->execute(
            (string) $this->input('email'),
            (string) $this->input('password'),
            $this->boolean('remember'),
            $this->ip() ?? '127.0.0.1'
        );
    }
}

