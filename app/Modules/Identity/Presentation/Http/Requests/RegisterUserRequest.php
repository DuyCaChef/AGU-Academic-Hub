<?php

namespace App\Modules\Identity\Presentation\Http\Requests;

use App\Modules\Identity\Domain\Services\IdentityRuleEvaluator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

// Form Request xác thực thông tin đăng ký tài khoản sinh viên mới
class RegisterUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    // Chuẩn hóa dữ liệu đầu vào trước khi xác thực
    protected function prepareForValidation(): void
    {
        $hoTen = $this->input('ho_ten') ?? $this->input('name');
        $mssv = $this->input('mssv');
        if (empty($mssv) && !empty($this->input('email'))) {
            $parts = explode('@', (string) $this->input('email'));
            $mssv = strtoupper($parts[0]);
        }

        $password = $this->input('mat_khau') ?? $this->input('password');
        $passwordConfirmation = $this->input('mat_khau_confirmation') ?? $this->input('password_confirmation');

        $this->merge([
            'ho_ten'    => $hoTen,
            'mssv'      => $mssv,
            'mat_khau'  => $password,
            'mat_khau_confirmation' => $passwordConfirmation,
        ]);
    }

    public function rules(): array
    {
        return [
            'ho_ten'   => ['required', 'string', 'max:255'],
            'mssv'     => ['nullable', 'string', 'max:50', 'unique:nguoi_dung,mssv'],
            'email'    => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:191',
                'unique:nguoi_dung,email',
            ],
            'mat_khau' => ['required', Password::defaults()],
        ];
    }

    // Không cho phép sinh viên đang học tự đăng ký tài khoản
    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $email = strtolower(trim((string) $this->input('email')));
            $mssv = strtoupper(trim((string) $this->input('mssv')));

            $isAguStudentEmail = str_ends_with($email, '@student.agu.edu.vn');
            $isInRawList = ($mssv !== '' && \App\Models\DanhSachSinhVienRaw::where('mssv', $mssv)->exists())
                || ($email !== '' && \App\Models\DanhSachSinhVienRaw::where('email_sv', $email)->exists());

            if ($isAguStudentEmail || $isInRawList) {
                $validator->errors()->add(
                    'email',
                    'Sinh viên đang học không cần tự đăng ký tài khoản. Vui lòng đăng nhập bằng MSSV hoặc email trường cấp cùng mật khẩu mặc định để kích hoạt tài khoản qua bước đổi mật khẩu lần đầu.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'ho_ten.required'    => 'Vui lòng nhập họ và tên.',
            'mssv.required'      => 'Vui lòng nhập mã số sinh viên.',
            'mssv.unique'        => 'Mã số sinh viên này đã tồn tại trong hệ thống.',
            'email.required'     => 'Vui lòng nhập email trường cấp.',
            'email.unique'       => 'Email này đã được đăng ký tài khoản trong hệ thống.',
            'mat_khau.required'  => 'Vui lòng nhập mật khẩu.',
        ];
    }
}
