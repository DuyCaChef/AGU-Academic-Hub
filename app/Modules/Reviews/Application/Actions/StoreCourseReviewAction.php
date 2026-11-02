<?php

namespace App\Modules\Reviews\Application\Actions;

use App\Models\DanhGiaMonHoc;
use App\Models\NguoiDung;
use App\Modules\Reviews\Domain\Services\ProfanityFilterEvaluator;
use App\Modules\Reviews\Infrastructure\Persistence\CourseReviewRepository;
use Illuminate\Validation\ValidationException;

// Action lưu bài đánh giá môn học mới kèm kiểm tra từ cấm lớp 2
class StoreCourseReviewAction
{
    public function __construct(
        protected CourseReviewRepository $reviewRepository,
        protected ProfanityFilterEvaluator $profanityFilter
    ) {}

    public function execute(NguoiDung $user, string $maHocPhan, array $data): DanhGiaMonHoc
    {
        // Chỉ cho tạo review khi Môn học và Khoa cha đều đang hiệu lực
        $monHoc = $this->reviewRepository->findCourseByCode($maHocPhan);
        $userId = $user->ma_nguoi_dung ?? $user->getKey();

        // Lớp bảo vệ 2: Kiểm tra từ cấm trước khi lưu vào CSDL
        $noiDung = trim((string) ($data['noi_dung'] ?? $data['nhan_xet'] ?? ''));
        $violations = $this->profanityFilter->layDanhSachViPham($noiDung);
        if (!empty($violations)) {
            $badWords = implode(', ', array_map(fn($w) => "\"{$w}\"", $violations));
            throw ValidationException::withMessages([
                'nhan_xet' => "Nội dung nhận xét chứa từ ngữ không phù hợp chuẩn mực: {$badWords}.",
                'noi_dung' => "Nội dung nhận xét chứa từ ngữ không phù hợp chuẩn mực: {$badWords}.",
            ]);
        }

        $workloadScore = (int) ($data['diem_khoi_luong'] ?? $data['diem_ap_luc_bai_tap'] ?? 3);
        $isAnonymous = (bool) ($data['an_danh'] ?? $data['la_an_danh'] ?? true);

        return $this->reviewRepository->createReview([
            'ma_mon_hoc'          => $monHoc->ma_mon_hoc ?? $monHoc->getKey(),
            'ma_nguoi_dung'       => $userId,
            'hoc_ky'              => $data['hoc_ky'],
            'diem_do_kho'         => (int) $data['diem_do_kho'],
            'diem_thuc_tien'      => (int) $data['diem_thuc_tien'],
            'diem_khoi_luong'     => $workloadScore,
            'noi_dung'            => $noiDung,
            'an_danh'             => $isAnonymous,
            'trang_thai'          => 'DA_DUYET',
        ]);
    }
}

