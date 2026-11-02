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
            'ly_do'    => ['required', 'string'],
            'chi_tiet' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'ly_do.required' => 'Vui lòng cung cấp lý do báo cáo vi phạm.',
        ];
    }
}

