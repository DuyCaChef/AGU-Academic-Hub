<?php

namespace App\Modules\Documents\Application\Queries;

use App\Models\TaiLieu;
use App\Modules\Documents\Infrastructure\Persistence\DocumentRepository;

class GetDocumentDetailQuery
{
    public function __construct(
        protected DocumentRepository $repository
    ) {}

    // Lấy thông tin chi tiết tài liệu phục vụ trang xem tài liệu
    public function execute(int $id): TaiLieu
    {
        return $this->repository->findById($id);
    }
}

