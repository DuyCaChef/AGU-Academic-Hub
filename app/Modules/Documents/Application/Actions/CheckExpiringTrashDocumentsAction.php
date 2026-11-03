<?php

namespace App\Modules\Documents\Application\Actions;

use App\Enums\VaiTroNguoiDung;
use App\Models\NguoiDung;
use App\Models\TaiLieu;
use App\Modules\Documents\Infrastructure\Notifications\DocumentsExpiringInTrashNotification;

class CheckExpiringTrashDocumentsAction
{
    /**
     * Quét và gửi thông báo cho các tài liệu sắp hết thời hạn lưu trữ trong thùng rác.
     *
     * @param int $warningDays Số ngày cảnh báo trước hạn (mặc định 7 ngày)
     * @return array Kết quả quét và gửi thông báo
     */
    public function execute(int $warningDays = 7): array
    {
        $purgeDays = (int) config('documents.trash_auto_purge_after_days', 90);
        $purgeWarningCutoff = now()->subDays(max(1, $purgeDays - $warningDays));
        $retentionWarningDate = now()->addDays($warningDays);

        $expiringDocuments = TaiLieu::onlyTrashed()
            ->where(function ($query) use ($purgeWarningCutoff, $retentionWarningDate): void {
                $query->whereNotNull('han_luu_thung_rac')
                    ->where('han_luu_thung_rac', '<=', $retentionWarningDate);
            })
            ->orWhere(function ($query) use ($purgeWarningCutoff): void {
                $query->onlyTrashed()
                    ->whereNotNull('ngay_xoa_tam')
                    ->where('ngay_xoa_tam', '<=', $purgeWarningCutoff);
            })
            ->select(['ma_tai_lieu', 'tieu_de', 'ngay_xoa_tam', 'han_luu_thung_rac', 'nguon_xoa'])
            ->limit(50)
            ->get();

        $count = $expiringDocuments->count();

        if ($count === 0) {
            return [
                'count' => 0,
                'documents' => [],
                'notified_count' => 0,
            ];
        }

        $documentsData = $expiringDocuments->map(fn (TaiLieu $doc) => [
            'ma_tai_lieu' => $doc->ma_tai_lieu,
            'tieu_de' => $doc->tieu_de,
            'ngay_xoa_tam' => $doc->ngay_xoa_tam?->toIso8601String(),
            'han_luu_thung_rac' => $doc->han_luu_thung_rac?->toIso8601String(),
            'nguon_xoa' => $doc->nguon_xoa,
        ])->all();

        $notification = new DocumentsExpiringInTrashNotification($count, $documentsData);

        $staff = NguoiDung::query()
            ->whereIn('vai_tro', [VaiTroNguoiDung::QUAN_TRI_VIEN, VaiTroNguoiDung::KIEM_DUYET_VIEN])
            ->get();

        foreach ($staff as $user) {
            $user->notify($notification);
        }

        return [
            'count' => $count,
            'documents' => $documentsData,
            'notified_count' => $staff->count(),
        ];
    }
}

