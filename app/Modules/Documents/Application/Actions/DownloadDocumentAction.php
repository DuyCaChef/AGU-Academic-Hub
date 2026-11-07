<?php

namespace App\Modules\Documents\Application\Actions;

use App\Enums\TrangThaiTaiLieu;
use App\Models\NguoiDung;
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

    // Xử lý tải tài liệu về máy và tự động tăng số lượt tải (Bảo vệ tài liệu chưa duyệt)
    public function execute(int $documentId, ?NguoiDung $user = null): Response
    {
        $document = $this->repository->findById($documentId);

        $isApproved = ($document->trang_thai === 'DA_DUYET' || $document->trang_thai === TrangThaiTaiLieu::DA_DUYET);
        if (!$isApproved) {
            $isAuthor = $user && ($document->ma_nguoi_dang === $user->ma_nguoi_dung);
            $isStaff = $user && (
                (method_exists($user, 'laQuanTriVien') && $user->laQuanTriVien()) ||
                (method_exists($user, 'laKiemDuyetVien') && $user->laKiemDuyetVien())
            );

            if (!$isAuthor && !$isStaff) {
                abort(403, 'Tài liệu này chưa được phê duyệt để tải về.');
            }
        }

        abort_unless($this->storage->exists($document->duong_dan_tep), 404, 'Tệp tài liệu không tồn tại.');

        // Tự động tăng lượt tải
        $this->repository->incrementDownloads($document);

        $displayName = $document->tieu_de . '.' . $document->dinh_dang_tep;

        return $this->storage->downloadResponse($document->duong_dan_tep, $displayName);
    }
}

