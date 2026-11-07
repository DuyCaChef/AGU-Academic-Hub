<?php

namespace App\Modules\Reviews\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// Form Request xác thực dữ liệu khi báo cáo vi phạm bài đánh giá
class ReportReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'ly_do'          => ['required', 'string'],
            'loai_doi_tuong' => ['nullable', 'string', 'in:MON_HOC,GIANG_VIEN'],
            'ten_giang_vien' => ['nullable', 'string', 'max:191'],
            'chi_tiet'       => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'ly_do.required' => 'Vui lòng cung cấp lý do báo cáo vi phạm.',
        ];
    }
}

