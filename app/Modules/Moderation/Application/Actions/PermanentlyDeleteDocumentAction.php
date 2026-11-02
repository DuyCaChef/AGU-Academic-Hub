<?php

namespace App\Modules\Moderation\Application\Actions;

use App\Models\LichSuThungRac;
use App\Models\NguoiDung;
use App\Modules\Moderation\Domain\Services\ModerationDecisionEvaluator;
use App\Modules\Moderation\Infrastructure\Persistence\TrashManagementRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PermanentlyDeleteDocumentAction
{
    public function __construct(
        protected TrashManagementRepository $repository,
        protected ModerationDecisionEvaluator $evaluator
    ) {}

    // Xóa vĩnh viễn (Hard Delete / Purge) tài liệu và file vật lý khỏi hệ thống sau 60 ngày
    public function execute(int $documentId, ?string $reason, NguoiDung $actor): void
    {
        DB::transaction(function () use ($documentId, $reason, $actor): void {
            $document = $this->repository->findTrashedForUpdate($documentId);

            $check = $this->evaluator->canPermanentlyDelete($document, $actor);
            abort_unless($check['allowed'], $check['status'], $check['message']);

            // 1. Lưu vết kiểm toán trước khi xóa sổ hoàn toàn
            LichSuThungRac::create([
                'ma_tai_lieu'        => $document->getKey(),
                'ma_nguoi_thuc_hien' => $actor->getKey(),
                'hanh_dong'          => 'PERMANENT_DELETE',
                'nguon'              => 'ADMIN',
                'ly_do'              => $reason ?: 'Xóa vĩnh viễn sau thời hạn lưu trữ.',
                'chi_tiet'           => ['duong_dan_tep' => $document->duong_dan_tep],
            ]);

            // 2. Xóa tệp vật lý trên đĩa cứng
            Storage::disk('local')->delete($document->duong_dan_tep);

            // 3. Xóa vĩnh viễn dòng dữ liệu trong CSDL
            $document->forceDelete();
        });
    }
}
