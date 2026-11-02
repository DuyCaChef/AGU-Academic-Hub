<?php

namespace App\Modules\Moderation\Application\Queries;

use App\Models\NguoiDung;
use App\Modules\Moderation\Infrastructure\Persistence\TrashManagementRepository;

class GetDeletedDocumentsQuery
{
    public function __construct(
        protected TrashManagementRepository $repository
    ) {}

    // Lấy danh sách tài liệu trong thùng rác phục vụ màn hình Admin/Documents/Deleted
    public function execute(array $filters = [], NguoiDung $user): array
    {
        $deletedDocuments = $this->repository->paginateDeletedDocuments($filters, 15);

        $actors = NguoiDung::query()
            ->whereIn('vai_tro', ['SINH_VIEN', 'CUU_SINH_VIEN', 'KIEM_DUYET_VIEN', 'QUAN_TRI_VIEN'])
            ->orderBy('ho_ten')
            ->get(['ma_nguoi_dung', 'ho_ten', 'vai_tro']);

        return [
            'documents'            => $deletedDocuments,
            'filters'              => array_intersect_key($filters, array_flip(['from', 'to', 'deleted_by', 'source', 'status'])),
            'actors'               => $actors,
            'sources'              => ['TAC_GIA', 'MODERATOR', 'ADMIN', 'SYSTEM'],
            'statuses'             => ['CHO_DUYET', 'DA_DUYET', 'CHO_KIEM_TRA', 'TAM_AN_KIEM_TRA', 'TU_CHOI'],
            'canPermanentlyDelete' => $user->laQuanTriVien(),
            'adminDeleteAfterDays' => config('documents.trash_admin_delete_after_days', 60),
            'autoPurgeAfterDays'   => config('documents.trash_auto_purge_after_days', 90),
        ];
    }
}
