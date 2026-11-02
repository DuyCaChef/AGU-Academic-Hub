<?php

namespace App\Modules\Identity\Presentation\Http\Requests;

use App\Models\NguoiDung;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// Form Request xác thực thông tin cập nhật hồ sơ cá nhân
class ProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $userId = $this->user()?->ma_nguoi_dung ?? $this->user()?->getKey();

        return [
            'name'  => ['nullable', 'string', 'max:255'],
            'ho_ten'=> ['nullable', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(NguoiDung::class, 'email')->ignore($userId, 'ma_nguoi_dung'),
            ],
        ];
    }
}

