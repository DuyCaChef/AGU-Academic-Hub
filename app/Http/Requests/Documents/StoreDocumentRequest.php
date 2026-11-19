<?php

namespace App\Http\Requests\Documents;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ma_mon_hoc' => ['required', 'integer', 'exists:mon_hoc,ma_mon_hoc'],
            'tieu_de' => ['required', 'string', 'max:255'],
            'mo_ta' => ['nullable', 'string', 'max:5000'],
            'phan_loai' => ['nullable', 'string', 'max:50'],
            'file' => ['required', 'file', 'max:51200'],
        ];
    }
}
