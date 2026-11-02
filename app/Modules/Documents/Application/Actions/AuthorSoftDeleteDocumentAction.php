<?php

namespace App\Modules\Documents\Application\Actions;

use App\Models\LichSuThungRac;
use App\Models\NguoiDung;
use App\Models\TaiLieu;
use App\Modules\Documents\Domain\Services\DocumentAuthorRemovalEvaluator;
use Illuminate\Support\Facades\DB;

class AuthorSoftDeleteDocumentAction
{
    public function __construct(
        protected DocumentAuthorRemovalEvaluator $evaluator
    ) {}

    // Tác giả tự xóa mềm tài liệu trong 24 giờ đầu và lượt tải = 0
    public function execute(int $documentId, string $reason, NguoiDung $user): void
    {
        $document = TaiLieu::query()->findOrFail($documentId);

        // Chốt chặn 3 lớp bảo vệ tài liệu
        $check = $this->evaluator->canAuthorSoftDelete($document, $user);
        abort_unless($check['allowed'], $check['status'], $check['message']);

        DB::transaction(function () use ($document, $reason, $user): void {
            // Cập nhật thông tin kiểm toán trước khi xóa mềm
            $document->forceFill([
                'ma_nguoi_xoa'      => $user->getKey(),
                'ly_do_xoa'         => $reason,
                'nguon_xoa'         => 'TAC_GIA',
                'han_luu_thung_rac' => now()->addDays(config('documents.trash_admin_delete_after_days')),
            ])->save();

            $document->delete();

            // Ghi nhật ký vào bảng kiểm toán thùng rác
            LichSuThungRac::create([
                'ma_tai_lieu'        => $document->getKey(),
                'ma_nguoi_thuc_hien' => $user->getKey(),
                'hanh_dong'          => 'SOFT_DELETE',
                'nguon'              => 'TAC_GIA',
                'ly_do'              => $reason,
            ]);
        });
    }
}

