<?php

namespace App\Modules\Reviews\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// Form Request xác thực dữ liệu khi chỉnh sửa bài đánh giá
class UpdateCourseReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'diem_do_kho'         => ['required', 'integer', 'min:1', 'max:5'],
            'diem_thuc_tien'      => ['required', 'integer', 'min:1', 'max:5'],
            'diem_khoi_luong'     => ['nullable', 'integer', 'min:1', 'max:5'],
            'diem_ap_luc_bai_tap' => ['nullable', 'integer', 'min:1', 'max:5'],
            'noi_dung'            => ['nullable', 'string', 'min:10', 'max:2000'],
            'nhan_xet'            => ['nullable', 'string', 'min:10', 'max:2000'],
            'an_danh'             => ['nullable', 'boolean'],
            'la_an_danh'          => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nhan_xet.required' => 'Nội dung nhận xét không được để trống.',
            'nhan_xet.min'      => 'Nội dung nhận xét cần tối thiểu 10 ký tự.',
            'noi_dung.required' => 'Nội dung nhận xét không được để trống.',
            'noi_dung.min'      => 'Nội dung nhận xét cần tối thiểu 10 ký tự.',
        ];
    }
}

