<?php

namespace App\Modules\Bounties\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// Form Request xác thực dữ liệu khi tạo yêu cầu tìm tài liệu mới
class StoreBountyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'ma_mon_hoc'     => ['required', 'integer', 'exists:mon_hoc,ma_mon_hoc'],
            'tieu_de'        => ['required', 'string', 'max:255'],
            'mo_ta'          => ['nullable', 'string'],
            'ten_giang_vien' => ['nullable', 'string', 'max:255'],
            'phan_loai'      => ['required', 'in:DE_THI,BAI_GIANG,DO_AN,BAI_TAP,KHAC'],
            'diem_thuong'    => ['required', 'integer', 'min:10', 'max:1000'],
        ];
    }
}

