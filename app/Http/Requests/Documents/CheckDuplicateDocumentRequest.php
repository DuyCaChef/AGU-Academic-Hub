<?php

namespace App\Http\Requests\Documents;

use Illuminate\Foundation\Http\FormRequest;

class CheckDuplicateDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ma_mon_hoc' => ['required', 'integer'],
            'ma_bam_file' => ['required', 'string'],
        ];
    }
}
