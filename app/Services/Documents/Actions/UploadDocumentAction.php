<?php

namespace App\Services\Documents\Actions;

use App\Jobs\ProcessDocumentSimilarityJob;
use App\Models\NguoiDung;
use App\Models\TaiLieu;
use App\Notifications\DocumentUploadedNotification;
use App\Services\Documents\DocumentDeduplicationEvaluator;
use App\Services\Documents\Storage\DocumentFileStorage;
use App\Services\Shared\ReputationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class UploadDocumentAction
{
    public function __construct(
        protected DocumentDeduplicationEvaluator $dedupEvaluator,
        protected DocumentFileStorage $storage,
        protected ReputationService $reputationService
    ) {}

    // Tiếp nhận và xử lý tải lên tài liệu mới đi qua Pipeline lọc trùng 2 lớp và tính điểm cống hiến
    public function execute(array $data, UploadedFile $file, NguoiDung $uploader): TaiLieu
    {
        // 1. Tính toán mã băm SHA-256 nhị phân của tệp tải lên
        $sha256Hash = hash_file('sha256', $file->getRealPath());

        // 2. Chặn trùng lặp 100% trong cùng môn học (Lớp 1: Băm nhị phân SHA-256)
        $courseId = (int) $data['ma_mon_hoc'];
        $existing = $this->dedupEvaluator->findExactDuplicateBySha256($courseId, $sha256Hash);

        if ($existing) {
            throw new ConflictHttpException('Tài liệu có nội dung giống 100% đã tồn tại trong môn học này.');
        }

        // 3. Phân tích độ tương đồng nội dung Lớp 2 (SimHash)
        $evalResult = $this->dedupEvaluator->evaluateFileSimilarity(
            maMonHoc: $courseId,
            filePath: $file->getRealPath(),
            extension: $file->getClientOriginalExtension()
        );

        $warningMessage = null;
        if ($evalResult['has_high_similarity'] && $evalResult['matched_document']) {
            $matched = $evalResult['matched_document'];
            $percent = round($evalResult['highest_similarity'] * 100, 1);
            $warningMessage = "Cảnh báo Lớp 2: Tương đồng {$percent}% với tài liệu #{$matched->ma_tai_lieu} ({$matched->tieu_de})";
        }

        return DB::transaction(function () use ($data, $file, $uploader, $sha256Hash, $evalResult, $warningMessage) {
            // 4. Lưu trữ tệp vật lý an toàn trong vùng private storage
            $storedPath = $this->storage->store($file, 'doc');
            $extension = strtolower($file->getClientOriginalExtension());
            $sizeKb = (int) ceil($file->getSize() / 1024);

            // 5. Tạo bản ghi tài liệu mới kèm chữ ký nội dung
            $document = TaiLieu::create([
                'ma_mon_hoc'       => $data['ma_mon_hoc'],
                'ma_nguoi_dang'    => $uploader->getKey(),
                'tieu_de'          => $data['tieu_de'],
                'mo_ta'            => $data['mo_ta'] ?? null,
                'duong_dan_tep'    => $storedPath,
                'dinh_dang_tep'    => $extension,
                'dung_luong_kb'    => $sizeKb,
                'ma_bam_file'      => $sha256Hash,
                'chu_ky_noi_dung'  => $evalResult['sim_hash'],
                'phan_loai'        => $data['phan_loai'] ?? 'KHAC',
                'phien_ban'        => '1.0',
                'luot_tai'         => 0,
                'trang_thai'       => 'DA_DUYET',
                'ly_do_trang_thai' => $warningMessage,
            ]);

            // 6. Cộng điểm thưởng cống hiến tri thức vào ví người tải lên (+10 điểm)
            $this->reputationService->congDiemDongGopTaiLieu($uploader, $document, 10);

            // 7. Kích hoạt Background Queue Job đối soát tương đồng sâu sau khi giao dịch commit
            DB::afterCommit(function () use ($document, $uploader) {
                ProcessDocumentSimilarityJob::dispatch($document->getKey());
                $uploader->notify(new DocumentUploadedNotification($document, 10));
            });

            return $document;
        });
    }
}
