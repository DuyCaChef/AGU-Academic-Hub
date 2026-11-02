<?php

namespace App\Modules\Documents\Application\Actions;

use App\Modules\Documents\Infrastructure\Persistence\DocumentRepository;
use App\Modules\Documents\Infrastructure\Storage\DocumentFileStorage;
use Symfony\Component\HttpFoundation\Response;

class PreviewDocumentAction
{
    public function __construct(
        protected DocumentRepository $repository,
        protected DocumentFileStorage $storage
    ) {}

    // Xem trước trực tuyến tài liệu PDF (Protected Inline Stream)
    public function execute(int $documentId, bool $withTrashed = false): Response
    {
        $document = $this->repository->findById($documentId, $withTrashed);

        abort_unless($this->storage->exists($document->duong_dan_tep), 404, 'Tệp tài liệu không tồn tại.');

        $displayName = basename($document->duong_dan_tep);

        return $this->storage->previewResponse($document->duong_dan_tep, $displayName);
    }
}
