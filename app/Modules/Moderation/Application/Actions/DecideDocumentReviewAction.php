<?php

namespace App\Modules\Moderation\Application\Actions;

use App\Models\BaoCaoViPham;
use App\Models\LichSuThungRac;
use App\Models\NguoiDung;
use App\Models\TaiLieu;
use App\Modules\Administration\Domain\Enums\HanhDongKiemToan;
use App\Modules\Administration\Domain\Enums\LoaiDoiTuongKiemToan;
use App\Modules\Administration\Domain\Services\AuditLogger;
use App\Modules\Moderation\Domain\Services\UserViolationPenaltyEvaluator;
use Illuminate\Support\Facades\DB;

class DecideDocumentReviewAction
{
    public function __construct(
        protected UserViolationPenaltyEvaluator $penaltyEvaluator
    ) {}

    // Quyết định xử lý tài liệu trong hàng đợi kiểm tra (KEEP, RESTORE, HIDE, DELETE)
    public function execute(int $documentId, string $action, string $reason, NguoiDung $actor): void
    {
        DB::transaction(function () use ($documentId, $action, $reason, $actor): void {
            $document = TaiLieu::withTrashed()->findOrFail($documentId);

            $actionLabel = match ($action) {
                'KEEP'    => 'giữ nguyên',
                'RESTORE' => 'khôi phục',
                'HIDE'    => 'ẩn',
                default   => 'xóa mềm',
            };

            $source = $actor->laQuanTriVien() ? 'ADMIN' : 'MODERATOR';

            if (in_array($action, ['KEEP', 'RESTORE'], true)) {
                $document->restore();
                $document->trang_thai = 'DA_DUYET';

                if ($action === 'RESTORE') {
                    LichSuThungRac::create([
                        'ma_tai_lieu'        => $document->getKey(),
                        'ma_nguoi_thuc_hien' => $actor->getKey(),
                        'hanh_dong'          => 'RESTORE',
                        'nguon'              => $source,
                        'ly_do'              => $reason,
                    ]);
                }
            } elseif ($action === 'HIDE') {
                $document->trang_thai = 'TAM_AN_KIEM_TRA';
            } else {
                $document->forceFill([
                    'ma_nguoi_xoa'      => $actor->getKey(),
                    'ly_do_xoa'         => $reason,
                    'nguon_xoa'         => $source,
                    'han_luu_thung_rac' => now()->addDays(config('documents.trash_admin_delete_after_days', 60)),
                ])->save();

                $document->delete();

                LichSuThungRac::create([
                    'ma_tai_lieu'        => $document->getKey(),
                    'ma_nguoi_thuc_hien' => $actor->getKey(),
                    'hanh_dong'          => 'SOFT_DELETE',
                    'nguon'              => $source,
                    'ly_do'              => $reason,
                ]);

                // Áp dụng chế tài phạt điểm uy tín và ghi nhận vi phạm cho tác giả tài liệu
                $document->loadMissing('nguoiDang');
                $penaltyPoints = (int) config('moderation.document_violation_penalty_points', 20);
                $this->penaltyEvaluator->applyPenalty(
                    author: $document->nguoiDang,
                    actor: $actor,
                    reason: $reason,
                    targetTitle: $document->tieu_de,
                    penaltyPoints: $penaltyPoints
                );
            }

            $document->ly_do_trang_thai = "Admin {$actionLabel}: {$reason}";
            $document->save();

            // Ghi nhật ký kiểm toán hành vi quản trị tài liệu
            $auditAction = match ($action) {
                'HIDE'    => HanhDongKiemToan::AN_TAI_LIEU,
                'RESTORE' => HanhDongKiemToan::KHOI_PHUC_TAI_LIEU,
                'KEEP'    => HanhDongKiemToan::XU_LY_BAO_CAO,
                default   => HanhDongKiemToan::XOA_MEM_TAI_LIEU,
            };

            app(AuditLogger::class)->log(
                actor: $actor,
                action: $auditAction,
                targetType: LoaiDoiTuongKiemToan::TAI_LIEU,
                targetId: (string) $document->getKey(),
                description: "Kiểm duyệt tài liệu \"{$document->tieu_de}\": {$actionLabel}. Lý do: {$reason}",
                oldData: ['trang_thai' => $document->getOriginal('trang_thai')],
                newData: ['trang_thai' => $document->trang_thai, 'action' => $action, 'ly_do' => $reason]
            );

            BaoCaoViPham::query()
                ->where('ma_tai_lieu', $document->getKey())
                ->where('trang_thai_xu_ly', 'CHO_XU_LY')
                ->update([
                    'trang_thai_xu_ly' => 'DA_XU_LY',
                    'ma_nguoi_xu_ly'   => $actor->getKey(),
                    'ly_do_xu_ly'      => $reason,
                    'xu_ly_luc'        => now(),
                ]);
        });
    }
}

