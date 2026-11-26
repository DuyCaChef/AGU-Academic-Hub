<?php

namespace App\Jobs;

use App\Models\TaiLieu;
use App\Services\Documents\DocumentDeduplicationEvaluator;
use App\Services\Documents\Storage\DocumentFileStorage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessDocumentSimilarityJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    public function __construct(
        public readonly int $documentId
    ) {}

    public function handle(
        DocumentDeduplicationEvaluator $evaluator,
        DocumentFileStorage $storage
    ): void {
        $document = TaiLieu::query()->find($this->documentId);
        if (!$document) {
            return;
        }

        // Đảm bảo tính Idempotency: Không xử lý lặp lại nếu đã có chu_ky_noi_dung
        if (!empty($document->chu_ky_noi_dung)) {
            return;
        }

        if (!$storage->exists($document->duong_dan_tep)) {
            Log::warning("ProcessDocumentSimilarityJob: File not found for document #{$this->documentId}");
            return;
        }

        $fullPath = $storage->fullPath($document->duong_dan_tep);

        try {
            $evalResult = $evaluator->evaluateFileSimilarity(
                maMonHoc: (int) $document->ma_mon_hoc,
                filePath: $fullPath,
                extension: (string) $document->dinh_dang_tep,
                excludeDocId: (int) $document->ma_tai_lieu
            );

            $updates = [
                'chu_ky_noi_dung' => $evalResult['sim_hash'],
            ];

            if ($evalResult['has_high_similarity'] && $evalResult['matched_document']) {
                $matched = $evalResult['matched_document'];
                $percent = round($evalResult['highest_similarity'] * 100, 1);
                $warningMsg = "Cảnh báo Lớp 2: Tương đồng {$percent}% với tài liệu #{$matched->ma_tai_lieu} ({$matched->tieu_de})";
                
                $updates['ly_do_trang_thai'] = $document->ly_do_trang_thai 
                    ? "{$document->ly_do_trang_thai} | {$warningMsg}" 
                    : $warningMsg;
            }

            $document->update($updates);
        } catch (\Throwable $e) {
            Log::error("ProcessDocumentSimilarityJob failed for doc #{$this->documentId}: " . $e->getMessage());
            throw $e;
        }
    }
}
