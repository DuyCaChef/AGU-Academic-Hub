<?php

namespace App\Modules\Moderation\Application\Actions;

use App\Models\BaoCaoViPham;
use App\Models\LichSuThungRac;
use App\Models\NguoiDung;
use App\Models\TaiLieu;
use Illuminate\Support\Facades\DB;

class DecideDocumentReviewAction
{
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
            }

            $document->ly_do_trang_thai = "Admin {$actionLabel}: {$reason}";
            $document->save();

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
