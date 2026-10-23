<?php

namespace App\Http\Requests;

use App\Enums\VaiTroNguoiDung;
use App\Models\DanhGiaMonHoc;
use App\Models\MonHoc;
use App\Shared\Services\ProfanityFilterService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreCourseReviewRequest extends FormRequest{
    public function authorize(): bool{
        $user = $this->user();

        // Chỉ tài khoản Sinh viên hoặc Cựu sinh viên đang hoạt động mới được gửi đánh giá
        $isRoleValid = $user && in_array($user->vai_tro, [
            VaiTroNguoiDung::SINH_VIEN, 
            VaiTroNguoiDung::CUU_SINH_VIEN
        ]);

        return $isRoleValid && $user->isActive();
    }

    public function rules(): array{
        return [
            'hoc_ky' => [
                'required',
                'string',
                'regex:/^20\d{2}-[1-3]$/', // Ví dụ: 2025-1, 2025-2, 2026-1
            ],
            'diem_do_kho'         => ['required', 'integer', 'min:1', 'max:5'],
            'diem_thuc_tien'      => ['required', 'integer', 'min:1', 'max:5'],
            'diem_ap_luc_bai_tap' => ['required', 'integer', 'min:1', 'max:5'],
            'nhan_xet'            => ['required', 'string', 'min:10', 'max:2000'],
            'la_an_danh'          => ['boolean'],
        ];
    }

    public function messages(): array{
        return [
            'hoc_ky.required'              => 'Vui lòng chọn học kỳ tham gia môn học.',
            'hoc_ky.regex'                 => 'Định dạng học kỳ không hợp lệ. Chuẩn mẫu: 2025-1 hoặc 2026-2.',
            'diem_do_kho.between'          => 'Điểm độ khó phải nằm trong khoảng từ 1 đến 5 sao.',
            'diem_thuc_tien.between'       => 'Điểm thực tiễn phải nằm trong khoảng từ 1 đến 5 sao.',
            'diem_ap_luc_bai_tap.between'  => 'Điểm áp lực bài tập phải nằm trong khoảng từ 1 đến 5 sao.',
            'nhan_xet.required'            => 'Nội dung chia sẻ kinh nghiệm không được để trống.',
            'nhan_xet.min'                 => 'Nội dung đánh giá cần tối thiểu 10 ký tự.',
            'nhan_xet.max'                 => 'Nội dung đánh giá không được vượt quá 2000 ký tự.',
        ];
    }

    // Hậu kiểm: Chống spam 1 SV/1 môn/1 học kỳ & Quét từ cấm.
   public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $user = $this->user();
            $maHocPhan = $this->route('ma_hoc_phan') ?? $this->route('course_code');
            $monHoc = MonHoc::where('ma_hoc_phan', $maHocPhan)->first();

            if (!$monHoc) {
                $validator->errors()->add('ma_hoc_phan', 'Mã học phần môn học không tồn tại trong hệ thống.');
                return;
            }

            // 1. Chống spam: Mỗi sinh viên chỉ được review 1 môn trong 1 học kỳ cụ thể
            $daDanhGia = DanhGiaMonHoc::where('ma_mon_hoc', $monHoc->ma_mon_hoc)
                ->where('ma_nguoi_danh_gia', $user->ma_nguoi_dung)
                ->where('hoc_ky', $this->input('hoc_ky'))
                ->exists();

            if ($daDanhGia) {
                $validator->errors()->add(
                    'hoc_ky',
                    "Bạn đã gửi đánh giá cho môn {$monHoc->ten_mon_hoc} trong học kỳ {$this->input('hoc_ky')} rồi."
                );
            }

            // 2. Quét từ ngữ thô tục qua ProfanityFilterService
            $filterService = app(ProfanityFilterService::class);
            $nhanXet = (string) $this->input('nhan_xet');
            $violations = $filterService->getViolations($nhanXet);

            if (!empty($violations)) {
                $badWordsList = implode(', ', array_map(fn($w) => "\"{$w}\"", $violations));
                $validator->errors()->add(
                    'nhan_xet',
                    "Nội dung nhận xét chứa từ ngữ không phù hợp chuẩn mực: {$badWordsList}. Vui lòng chỉnh sửa trước khi gửi."
                );
            }
        });
    }

}