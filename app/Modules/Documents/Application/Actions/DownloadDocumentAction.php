<?php

namespace App\Modules\Documents\Application\Actions;

use App\Models\TaiLieu;
use App\Modules\Documents\Infrastructure\Persistence\DocumentRepository;
use App\Modules\Documents\Infrastructure\Storage\DocumentFileStorage;
use Symfony\Component\HttpFoundation\Response;

class DownloadDocumentAction
{
    public function __construct(
        protected DocumentRepository $repository,
        protected DocumentFileStorage $storage
    ) {}

    // Xử lý tải tài liệu về máy và tự động tăng số lượt tải
    public function execute(int $documentId): Response
    {
        $document = $this->repository->findById($documentId);

        abort_unless($this->storage->exists($document->duong_dan_tep), 404, 'Tệp tài liệu không tồn tại.');

        // Tự động tăng lượt tải
        $this->repository->incrementDownloads($document);

        $displayName = $document->tieu_de . '.' . $document->dinh_dang_tep;

        return $this->storage->downloadResponse($document->duong_dan_tep, $displayName);
    }
}
