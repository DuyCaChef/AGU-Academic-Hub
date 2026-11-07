<?php

namespace App\Modules\Moderation\Domain\Services;

use App\Enums\TrangThaiDanhGiaMonHoc;
use App\Models\BaoCaoDanhGia;
use App\Models\DanhGiaMonHoc;
use App\Models\NguoiDung;
use App\Modules\Moderation\Infrastructure\Notifications\NewReviewViolationReportNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviewReportRecalculator
{
    // Tạo bản ghi báo cáo vi phạm đánh giá môn học mới (chống spam trùng từ cùng 1 tài khoản)
    public function createReport(DanhGiaMonHoc $review, NguoiDung $reporter, array $data): BaoCaoDanhGia
    {
        return DB::transaction(function () use ($review, $reporter, $data): BaoCaoDanhGia {
            $lockedReview = DanhGiaMonHoc::withTrashed()->lockForUpdate()->findOrFail($review->getKey());

            $existing = BaoCaoDanhGia::withTrashed()
                ->where('ma_danh_gia', $lockedReview->getKey())
                ->where('ma_nguoi_bao_cao', $reporter->getKey())
                ->first();

            if ($existing && $existing->trang_thai === 'CHO_XU_LY') {
                throw ValidationException::withMessages([
                    'report' => 'Bạn đã gửi báo cáo cho bài đánh giá này và đang được Ban kiểm duyệt xem xét.',
                ]);
            }

            $validReasons = ['NGON_TU_KICH_DONG', 'BOI_NHO', 'QUAY_ROI', 'SPAM', 'KHAC'];
            $reason = in_array($data['ly_do'] ?? '', $validReasons, true) ? $data['ly_do'] : 'KHAC';

            // Xác định loại mục tiêu bị phản ánh (Môn học hoặc Giảng viên)
            $loaiDoiTuong = $data['loai_doi_tuong'] ?? ($reason === 'BOI_NHO' ? 'GIANG_VIEN' : 'MON_HOC');
            if (!in_array($loaiDoiTuong, ['MON_HOC', 'GIANG_VIEN'], true)) {
                $loaiDoiTuong = 'MON_HOC';
            }

            $tenGiangVien = !empty($data['ten_giang_vien']) ? trim((string) $data['ten_giang_vien']) : null;

            $report = BaoCaoDanhGia::updateOrCreate(
                [
                    'ma_danh_gia'      => $lockedReview->getKey(),
                    'ma_nguoi_bao_cao' => $reporter->getKey(),
                ],
                [
                    'loai_doi_tuong'  => $loaiDoiTuong,
                    'ten_giang_vien'  => $tenGiangVien,
                    'ly_do'           => $reason,
                    'chi_tiet'        => $data['chi_tiet'] ?? null,
                    'trang_thai'      => 'CHO_XU_LY',
                    'nguoi_xu_ly'     => null,
                    'thoi_gian_xu_ly' => null,
                    'ghi_chu_xu_ly'   => null,
                    'deleted_at'      => null,
                ]
            );

            // Gửi thông báo đến toàn bộ Admin và Moderator
            $staff = NguoiDung::query()
                ->whereIn('vai_tro', ['KIEM_DUYET_VIEN', 'QUAN_TRI_VIEN'])
                ->get();

            foreach ($staff as $recipient) {
                $recipient->notify(new NewReviewViolationReportNotification($report, $lockedReview, $reporter));
            }

            $this->recalculate($lockedReview, true);

            return $report;
        });
    }

    // Tính toán lại số lượng báo cáo và tự động tạm ẩn nếu đạt ngưỡng vi phạm
    public function recalculate(DanhGiaMonHoc $review, bool $notify = false): DanhGiaMonHoc
    {
        $pendingReportsCount = $review->baoCaos()
            ->where('trang_thai', 'CHO_XU_LY')
            ->count();

        $autoHideThreshold = (int) config('moderation.review_auto_hide_threshold', 3);

        // Tự động tạm ẩn bài đánh giá nếu tích lũy đủ báo cáo chờ xử lý
        if ($pendingReportsCount >= $autoHideThreshold && $review->trang_thai === TrangThaiDanhGiaMonHoc::DA_DUYET) {
            $review->trang_thai = TrangThaiDanhGiaMonHoc::TAM_AN;
            $review->save();
        }

        return $review;
    }
}

