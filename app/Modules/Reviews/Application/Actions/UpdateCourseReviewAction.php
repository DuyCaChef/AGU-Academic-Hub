<?php

namespace App\Modules\Reviews\Application\Actions;

use App\Models\DanhGiaMonHoc;
use App\Models\NguoiDung;
use App\Modules\Reviews\Domain\Services\CourseReviewRuleEvaluator;
use App\Modules\Reviews\Domain\Services\ProfanityFilterEvaluator;
use App\Modules\Reviews\Infrastructure\Persistence\CourseReviewRepository;
use Illuminate\Validation\ValidationException;

// Action cập nhật nội dung bài đánh giá môn học
class UpdateCourseReviewAction
{
    public function __construct(
        protected CourseReviewRepository $reviewRepository,
        protected CourseReviewRuleEvaluator $ruleEvaluator,
        protected ProfanityFilterEvaluator $profanityFilter
    ) {}

    public function execute(NguoiDung $user, int $maDanhGia, array $data): DanhGiaMonHoc
    {
        $danhGia = $this->reviewRepository->findReviewById($maDanhGia);

        // Phân quyền: Chỉ chính tác giả mới được quyền sửa
        if (!$this->ruleEvaluator->canEdit($user, $danhGia)) {
            abort(403, 'Bạn không có quyền chỉnh sửa bài đánh giá này.');
        }

        $noiDung = trim((string) ($data['noi_dung'] ?? $data['nhan_xet'] ?? $danhGia->noi_dung));
        if (mb_strlen($noiDung) < 10) {
            throw ValidationException::withMessages([
                'nhan_xet' => 'Nội dung nhận xét cần tối thiểu 10 ký tự.',
                'noi_dung' => 'Nội dung nhận xét cần tối thiểu 10 ký tự.',
            ]);
        }

        // Kiểm tra từ cấm cho nội dung chỉnh sửa
        $violations = $this->profanityFilter->layDanhSachViPham($noiDung);
        if (!empty($violations)) {
            $badWords = implode(', ', array_map(fn($w) => "\"{$w}\"", $violations));
            throw ValidationException::withMessages([
                'nhan_xet' => "Nội dung nhận xét chứa từ ngữ không phù hợp chuẩn mực: {$badWords}.",
                'noi_dung' => "Nội dung nhận xét chứa từ ngữ không phù hợp chuẩn mực: {$badWords}.",
            ]);
        }

        $workloadScore = $data['diem_khoi_luong'] ?? $data['diem_ap_luc_bai_tap'] ?? $danhGia->diem_khoi_luong;
        $isAnonymous = $data['an_danh'] ?? $data['la_an_danh'] ?? $danhGia->an_danh;

        $this->reviewRepository->updateReview($danhGia, [
            'diem_do_kho'     => $data['diem_do_kho'],
            'diem_thuc_tien'  => $data['diem_thuc_tien'],
            'diem_khoi_luong' => $workloadScore,
            'noi_dung'        => $noiDung,
            'an_danh'         => $isAnonymous,
        ]);

        return $danhGia->fresh();
    }
}

