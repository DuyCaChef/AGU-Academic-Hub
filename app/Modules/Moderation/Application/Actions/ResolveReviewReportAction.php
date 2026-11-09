<?php

namespace App\Modules\Moderation\Application\Actions;

use App\Enums\TrangThaiDanhGiaMonHoc;
use App\Models\BaoCaoDanhGia;
use App\Models\DanhGiaMonHoc;
use App\Models\NguoiDung;
use App\Modules\Administration\Domain\Enums\HanhDongKiemToan;
use App\Modules\Administration\Domain\Enums\LoaiDoiTuongKiemToan;
use App\Modules\Administration\Domain\Services\AuditLogger;
use App\Modules\Moderation\Domain\Services\UserViolationPenaltyEvaluator;
use App\Modules\Moderation\Infrastructure\Notifications\ReviewReportResolvedNotification;
use Illuminate\Support\Facades\DB;

class ResolveReviewReportAction
{
    public function __construct(
        protected UserViolationPenaltyEvaluator $penaltyEvaluator
    ) {}

    // Quyết định giải quyết báo cáo đánh giá: Tạm ẩn, Xóa hoặc Bác bỏ (hỗ trợ cả môn học và giảng viên)
    public function execute(int $reportId, string $action, string $reason, NguoiDung $actor): string
    {
        return DB::transaction(function () use ($reportId, $action, $reason, $actor): string {
            $report = BaoCaoDanhGia::with(['danhGia.monHoc', 'danhGia.nguoiDung', 'nguoiBaoCao'])
                ->findOrFail($reportId);

            // Kiểm tra loại đối tượng và truy vấn bài đánh giá liên kết nếu có
            $review = $report->ma_danh_gia
                ? DanhGiaMonHoc::withTrashed()->find($report->ma_danh_gia)
                : null;

            $now = now();
            $status = ($action === 'DISMISS') ? 'BAC_BO' : 'DA_XU_LY';

            $actionLabel = match ($action) {
                'HIDE'   => 'tạm ẩn để kiểm tra',
                'DELETE' => 'gỡ bỏ khỏi hệ thống',
                default  => 'bác bỏ',
            };

            $isLecturer = ($report->loai_doi_tuong === 'GIANG_VIEN') || ($report->ly_do === 'BOI_NHO');
            $lecturerName = $report->ten_giang_vien;
            $courseCode = $review?->monHoc?->ma_mon_hoc ?? ($review?->ma_mon_hoc ? "Môn #{$review->ma_mon_hoc}" : null);

            $targetDescription = match (true) {
                $isLecturer && $lecturerName && $courseCode => "giảng viên {$lecturerName} (môn {$courseCode})",
                $isLecturer && $lecturerName => "giảng viên {$lecturerName}",
                !empty($courseCode) => "môn {$courseCode}",
                default => "bài đánh giá #{$reportId}",
            };

            // 1. Áp dụng hành động đối với bài đánh giá nếu có bản ghi liên kết
            if ($review) {
                if ($action === 'HIDE') {
                    $review->update(['trang_thai' => TrangThaiDanhGiaMonHoc::TAM_AN->value]);
                } elseif ($action === 'DELETE') {
                    $review->update(['trang_thai' => TrangThaiDanhGiaMonHoc::TU_CHOI->value]);
                    if (!$review->trashed()) {
                        $review->delete();
                    }

                    // Áp dụng chế tài phạt điểm uy tín và ghi nhận vi phạm cho tác giả bài đánh giá
                    $review->loadMissing('nguoiDung');
                    if ($review->nguoiDung) {
                        $penaltyPoints = (int) config('moderation.review_violation_penalty_points', 10);
                        $this->penaltyEvaluator->applyPenalty(
                            author: $review->nguoiDung,
                            actor: $actor,
                            reason: $reason,
                            targetTitle: "Bài đánh giá {$targetDescription}",
                            penaltyPoints: $penaltyPoints
                        );
                    }
                } elseif ($action === 'DISMISS') {
                    // Nếu trước đó bài bị tạm ẩn do auto-hide, khôi phục lại trạng thái đã duyệt
                    if ($review->trang_thai === TrangThaiDanhGiaMonHoc::TAM_AN) {
                        $review->update(['trang_thai' => TrangThaiDanhGiaMonHoc::DA_DUYET->value]);
                    }
                }
            }

            // 2. Cập nhật các bản ghi báo cáo (bao gồm báo cáo hiện tại và các báo cáo chờ duyệt cùng bài viết)
            $reportsToResolve = ($action === 'DISMISS')
                ? collect([$report])
                : BaoCaoDanhGia::with('nguoiBaoCao')
                    ->when($report->ma_danh_gia, function ($q) use ($report) {
                        $q->where('ma_danh_gia', $report->ma_danh_gia);
                    }, function ($q) use ($report) {
                        $q->where('ma_bao_cao', $report->ma_bao_cao);
                    })
                    ->where(function ($q) use ($report) {
                        $q->where('trang_thai', 'CHO_XU_LY')
                          ->orWhere('ma_bao_cao', $report->ma_bao_cao);
                    })
                    ->get();

            foreach ($reportsToResolve as $relatedReport) {
                $relatedReport->update([
                    'trang_thai'      => $status,
                    'nguoi_xu_ly'     => $actor->getKey(),
                    'thoi_gian_xu_ly' => $now,
                    'ghi_chu_xu_ly'   => $reason,
                ]);
            }

            // 3. Gửi thông báo đến những người đã báo cáo
            foreach ($reportsToResolve as $resolvedReport) {
                if ($resolvedReport->nguoiBaoCao) {
                    $resolvedReport->nguoiBaoCao->notify(
                        new ReviewReportResolvedNotification($resolvedReport, $actionLabel)
                    );
                }
            }

            // 4. Nếu bài đánh giá bị ẩn hoặc xóa, gửi thông báo đến tác giả viết bài
            if (in_array($action, ['HIDE', 'DELETE'], true) && $review?->nguoiDung) {
                $review->nguoiDung->notify(
                    new ReviewReportResolvedNotification(
                        $report,
                        $actionLabel,
                        "Bài đánh giá của bạn liên quan đến {$targetDescription} đã bị Ban kiểm duyệt {$actionLabel} do vi phạm chuẩn mực cộng đồng. Căn cứ xử lý: {$reason}."
                    )
                );
            }

            // 5. Ghi nhật ký kiểm toán (Audit Log)
            app(AuditLogger::class)->log(
                actor: $actor,
                action: HanhDongKiemToan::XU_LY_BAO_CAO_DANH_GIA,
                targetType: LoaiDoiTuongKiemToan::BAO_CAO_DANH_GIA,
                targetId: (string) $reportId,
                description: "Xử lý báo cáo đánh giá #{$reportId} ({$targetDescription}): {$actionLabel}. Lý do: {$reason}",
                oldData: ['trang_thai' => $report->trang_thai],
                newData: ['trang_thai' => $status, 'action' => $action, 'ghi_chu' => $reason]
            );

            if ($review) {
                if ($action === 'HIDE') {
                    app(AuditLogger::class)->log(
                        actor: $actor,
                        action: HanhDongKiemToan::AN_DANH_GIA,
                        targetType: LoaiDoiTuongKiemToan::DANH_GIA_MON_HOC,
                        targetId: (string) $review->getKey(),
                        description: "Tạm ẩn đánh giá {$targetDescription} do vi phạm chuẩn mực. Lý do: {$reason}",
                        oldData: ['trang_thai' => $review->getOriginal('trang_thai')],
                        newData: ['trang_thai' => TrangThaiDanhGiaMonHoc::TAM_AN->value]
                    );
                } elseif ($action === 'DELETE') {
                    app(AuditLogger::class)->log(
                        actor: $actor,
                        action: HanhDongKiemToan::XOA_DANH_GIA,
                        targetType: LoaiDoiTuongKiemToan::DANH_GIA_MON_HOC,
                        targetId: (string) $review->getKey(),
                        description: "Gỡ bỏ bài đánh giá {$targetDescription} do vi phạm tiêu chuẩn nghiêm trọng. Lý do: {$reason}",
                        oldData: ['deleted_at' => null],
                        newData: ['deleted_at' => $now->toIso8601String(), 'trang_thai' => TrangThaiDanhGiaMonHoc::TU_CHOI->value]
                    );
                }
            }

            return $actionLabel;
        });
    }
}

