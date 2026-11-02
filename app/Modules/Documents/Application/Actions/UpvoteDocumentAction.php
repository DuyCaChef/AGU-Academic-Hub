<?php

namespace App\Modules\Documents\Application\Actions;

use App\Models\NguoiDung;
use App\Models\TaiLieu;
use App\Modules\Documents\Infrastructure\Persistence\DocumentRepository;

class UpvoteDocumentAction
{
    public function __construct(
        protected DocumentRepository $repository
    ) {}

    // Bình chọn / Thích tài liệu
    public function execute(int $documentId, NguoiDung $user): int
    {
        $document = $this->repository->findById($documentId);

        // Tăng số lượt bình chọn (hoặc lưu liên kết user upvote)
        $document->increment('luot_binh_chon');

        return (int) $document->luot_binh_chon;
    }
}
