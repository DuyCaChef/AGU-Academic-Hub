<?php

namespace App\Http\Requests\Moderation;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'ma_tai_lieu'  => ['required', 'integer', 'exists:tai_lieu,ma_tai_lieu'],
            'ly_do'        => ['required', 'in:TRUNG_LAP,SAI_NOI_DUNG,MA_DOC,BAN_QUYEN,KHAC'],
            'chi_tiet_loi' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
