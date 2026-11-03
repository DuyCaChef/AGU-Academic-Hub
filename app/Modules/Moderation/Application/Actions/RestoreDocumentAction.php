<?php

namespace App\Modules\Moderation\Application\Actions;

use App\Models\BaoCaoViPham;
use App\Models\LichSuThungRac;
use App\Models\NguoiDung;
use App\Models\TaiLieu;
use App\Modules\Administration\Domain\Enums\HanhDongKiemToan;
use App\Modules\Administration\Domain\Enums\LoaiDoiTuongKiemToan;
use App\Modules\Administration\Domain\Services\AuditLogger;
use App\Modules\Moderation\Infrastructure\Persistence\TrashManagementRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RestoreDocumentAction
{
    public function __construct(
        protected TrashManagementRepository $repository
    ) {}

    // Khôi phục tài liệu đã xóa mềm từ thùng rác (Chỉ dành cho Admin)
    public function execute(int $documentId, bool $confirmViolation, ?string $reason, NguoiDung $actor): TaiLieu
    {
        abort_unless($actor->laQuanTriVien(), 403, 'Chỉ Quản trị viên mới có quyền khôi phục tài liệu.');

        return DB::transaction(function () use ($documentId, $confirmViolation, $reason, $actor): TaiLieu {
            $document = $this->repository->findTrashedForUpdate($documentId);

            if ($document->ma_tai_lieu_goc && !$document->taiLieuGoc) {
                abort(409, 'Không thể khôi phục: phiên bản gốc không còn tồn tại.');
            }

            $hasActiveViolations = BaoCaoViPham::query()
                ->where('ma_tai_lieu', $document->getKey())
                ->where('trang_thai_xu_ly', 'CHO_XU_LY')
                ->exists();

            if ($hasActiveViolations && !$confirmViolation) {
                throw ValidationException::withMessages([
                    'document' => 'Tài liệu đang có báo cáo vi phạm chờ xử lý. Vui lòng xác nhận để khôi phục và chuyển tài liệu vào hàng đợi kiểm tra.',
                ]);
            }

            $document->restore();
            $document->update([
                'trang_thai'       => $hasActiveViolations ? 'CHO_KIEM_TRA' : 'DA_DUYET',
                'ly_do_trang_thai' => $hasActiveViolations
                    ? 'Khôi phục trong khi còn báo cáo vi phạm chờ xử lý.'
                    : null,
            ]);

            $source = $actor->laQuanTriVien() ? 'ADMIN' : 'MODERATOR';

            LichSuThungRac::create([
                'ma_tai_lieu'        => $document->getKey(),
                'ma_nguoi_thuc_hien' => $actor->getKey(),
                'hanh_dong'          => 'RESTORE',
                'nguon'              => $source,
                'ly_do'              => $reason ?: 'Khôi phục tài liệu từ thùng rác.',
                'chi_tiet'           => ['co_bao_cao_cho_xu_ly' => $hasActiveViolations],
            ]);

            app(AuditLogger::class)->log(
                actor: $actor,
                action: HanhDongKiemToan::KHOI_PHUC_TAI_LIEU,
                targetType: LoaiDoiTuongKiemToan::TAI_LIEU,
                targetId: (string) $document->getKey(),
                description: "Khôi phục tài liệu \"{$document->tieu_de}\" từ thùng rác.",
                oldData: ['trang_thai' => 'DA_XOA'],
                newData: ['trang_thai' => $document->trang_thai, 'ly_do' => $reason]
            );

            return $document;
        });
    }
}

