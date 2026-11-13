<?php

namespace App\Modules\Documents\Application\Queries;

use App\Enums\TrangThaiTaiLieu;
use App\Models\NguoiDung;
use App\Models\TaiLieu;
use App\Modules\Documents\Infrastructure\Persistence\DocumentRepository;

class GetDocumentDetailQuery
{
    public function __construct(
        protected DocumentRepository $repository
    ) {}

    // Lấy thông tin chi tiết tài liệu phục vụ trang xem tài liệu (Kiểm soát quyền xem tài liệu chưa duyệt)
    public function execute(int $id, ?NguoiDung $user = null): TaiLieu
    {
        $document = $this->repository->findById($id);

        $isApproved = ($document->trang_thai === 'DA_DUYET' || $document->trang_thai === TrangThaiTaiLieu::DA_DUYET);
        if (!$isApproved) {
            $isAuthor = $user && ($document->ma_nguoi_dang === $user->ma_nguoi_dung);
            $isStaff = $user && (
                (method_exists($user, 'laQuanTriVien') && $user->laQuanTriVien()) ||
                (method_exists($user, 'laKiemDuyetVien') && $user->laKiemDuyetVien())
            );

            if (!$isAuthor && !$isStaff) {
                abort(403, 'Tài liệu này chưa được phê duyệt hoặc đang bị tạm ẩn kiểm tra.');
            }
        }

        $document->has_upvoted = $user ? $document->daBinhChonBoi($user) : false;

        return $document;
    }
}

