<?php

namespace App\Http\Requests;

use App\Models\DanhSachSinhVienRaw;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AlumniKycRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => strtolower(trim((string) $this->input('email'))),
            'mssv' => strtoupper(trim((string) $this->input('mssv'))),
        ]);
    }

    public function rules(): array
    {
        return [
            'ho_ten'          => ['required', 'string', 'max:255'],
            'email'           => ['required', 'string', 'email', 'max:255', 'unique:nguoi_dung,email'],
            'mssv'            => ['required', 'string', 'max:50'],
            'nam_tot_nghiep'  => ['required', 'integer', 'min:1999', 'max:' . date('Y')],
            'ma_khoa'         => ['nullable', 'exists:khoa,ma_khoa'],
            'mat_khau'        => ['required', 'confirmed', Password::defaults()],
            'loai_minh_chung' => ['required', Rule::in(['BANG_TOT_NGHIEP', 'GIAY_CHUNG_NHAN', 'THE_SINH_VIEN'])],
            'anh_minh_chung'  => [
                'required',
                'file',
                'image',
                'mimes:jpeg,png,jpg,webp',
                'max:5120', // Tối đa 5MB
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $mssv = (string) $this->input('mssv');

            if ($mssv !== '' && DanhSachSinhVienRaw::where('mssv', $mssv)->exists()) {
                $validator->errors()->add(
                    'mssv',
                    'MSSV này thuộc danh sách sinh viên đang học. Sinh viên hiện hành không nộp hồ sơ KYC cựu sinh viên.'
                );
            }
        });
    }

    /**
     * Tùy biến thông báo lỗi xác thực form nộp hồ sơ KYC cựu sinh viên.
     */
    public function messages(): array
    {
        return [
            'ho_ten.required'          => 'Vui lòng nhập họ và tên cựu sinh viên.',
            'email.required'           => 'Vui lòng cung cấp email liên hệ.',
            'email.unique'             => 'Email này đã được sử dụng trên hệ thống.',
            'mssv.required'            => 'Vui lòng cung cấp mã số sinh viên khóa cũ.',
            'nam_tot_nghiep.required'  => 'Vui lòng cung cấp năm tốt nghiệp.',
            'nam_tot_nghiep.min'       => 'Năm tốt nghiệp không được trước năm 1999.',
            'nam_tot_nghiep.max'       => 'Năm tốt nghiệp không được vượt quá năm hiện tại.',
            'mat_khau.confirmed'       => 'Mật khẩu xác nhận không trùng khớp.',
            'loai_minh_chung.required' => 'Vui lòng chọn loại tài liệu minh chứng.',
            'loai_minh_chung.in'       => 'Loại minh chứng không hợp lệ.',
            'anh_minh_chung.required'  => 'Vui lòng tải lên ảnh chụp minh chứng tốt nghiệp hoặc thẻ SV cũ.',
            'anh_minh_chung.image'     => 'Tệp tải lên bắt buộc phải là định dạng hình ảnh.',
            'anh_minh_chung.mimes'     => 'Chỉ chấp nhận ảnh định dạng JPEG, PNG, JPG hoặc WEBP.',
            'anh_minh_chung.max'       => 'Dung lượng ảnh tải lên không được vượt quá 5MB.',
        ];
    }
}