<?php

namespace App\Modules\Administration\Application\Queries;

use App\Modules\Administration\Domain\Enums\HanhDongKiemToan;
use App\Modules\Administration\Domain\Enums\LoaiDoiTuongKiemToan;
use App\Modules\Administration\Infrastructure\Persistence\AuditLogRepository;

class GetAuditLogsQuery
{
    public function __construct(
        protected AuditLogRepository $repository
    ) {}

    public function execute(array $filters = []): array
    {
        $paginator = $this->repository->getPaginated($filters, 25);
        $stats = $this->repository->getSummaryStats();

        $paginator->getCollection()->transform(function ($log) {
            $actionEnum = $log->hanh_dong instanceof HanhDongKiemToan
                ? $log->hanh_dong
                : HanhDongKiemToan::tryFrom($log->hanh_dong);

            $targetTypeEnum = $log->loai_doi_tuong instanceof LoaiDoiTuongKiemToan
                ? $log->loai_doi_tuong
                : LoaiDoiTuongKiemToan::tryFrom($log->loai_doi_tuong);

            return [
                'id'             => $log->id,
                'hanh_dong'      => [
                    'value' => $log->hanh_dong instanceof HanhDongKiemToan ? $log->hanh_dong->value : (string) $log->hanh_dong,
                    'label' => $actionEnum ? $actionEnum->label() : (string) $log->hanh_dong,
                    'color' => $actionEnum ? $actionEnum->color() : 'slate',
                ],
                'loai_doi_tuong' => [
                    'value' => $log->loai_doi_tuong instanceof LoaiDoiTuongKiemToan ? $log->loai_doi_tuong->value : (string) $log->loai_doi_tuong,
                    'label' => $targetTypeEnum ? $targetTypeEnum->label() : (string) $log->loai_doi_tuong,
                ],
                'ma_doi_tuong'   => $log->ma_doi_tuong,
                'mo_ta'          => $log->mo_ta,
                'du_lieu_cu'     => $log->du_lieu_cu,
                'du_lieu_moi'    => $log->du_lieu_moi,
                'dia_chi_ip'     => $log->dia_chi_ip,
                'user_agent'     => $log->user_agent,
                'created_at'     => $log->created_at?->toIso8601String(),
                'thoi_gian'      => $log->created_at?->format('H:i:s d/m/Y'),
                'thoi_gian_truoc'=> $log->created_at?->diffForHumans(),
                'nguoi_thuc_hien'=> $log->nguoiThucHien ? [
                    'ma_nguoi_dung' => $log->nguoiThucHien->ma_nguoi_dung,
                    'ho_ten'        => $log->nguoiThucHien->ho_ten,
                    'email'         => $log->nguoiThucHien->email,
                    'vai_tro'       => $log->nguoiThucHien->vai_tro?->value ?? (string) $log->nguoiThucHien->vai_tro,
                    'cap_quan_tri'  => $log->nguoiThucHien->cap_quan_tri?->value ?? (string) $log->nguoiThucHien->cap_quan_tri,
                ] : null,
            ];
        });

        $availableActions = array_map(fn (HanhDongKiemToan $case) => [
            'value' => $case->value,
            'label' => $case->label(),
        ], HanhDongKiemToan::cases());

        $availableTargetTypes = array_map(fn (LoaiDoiTuongKiemToan $case) => [
            'value' => $case->value,
            'label' => $case->label(),
        ], LoaiDoiTuongKiemToan::cases());

        return [
            'logs'                   => $paginator,
            'filters'                => $filters,
            'stats'                  => $stats,
            'available_actions'      => $availableActions,
            'available_target_types' => $availableTargetTypes,
        ];
    }
}

