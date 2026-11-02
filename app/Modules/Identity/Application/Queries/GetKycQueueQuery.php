<?php

namespace App\Modules\Identity\Application\Queries;

use App\Modules\Identity\Infrastructure\Persistence\KycRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\URL;

class GetKycQueueQuery
{
    public function __construct(
        protected KycRepository $repository
    ) {}

    // Lấy danh sách hàng đợi duyệt hồ sơ cựu sinh viên kèm Signed URL xem trước 30 phút
    public function execute(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository
            ->getPendingAlumniKycPaginated($perPage)
            ->through(function ($item) {
                return [
                    'ma_xac_thuc'     => $item->ma_yeu_cau_xac_thuc,
                    'ma_nguoi_dung'   => $item->ma_nguoi_dung,
                    'ho_ten'          => $item->nguoiDung?->ho_ten,
                    'email'           => $item->nguoiDung?->email,
                    'khoa'            => $item->nguoiDung?->khoa ? [
                        'ma_khoa'      => $item->nguoiDung->khoa->ma_khoa,
                        'ten_khoa'     => $item->nguoiDung->khoa->ten_khoa,
                        'ky_hieu_khoa' => $item->nguoiDung->khoa->ky_hieu_khoa,
                    ] : null,
                    'mssv'            => $item->mssv,
                    'nam_tot_nghiep'  => $item->nam_tot_nghiep,
                    'loai_minh_chung' => $item->loai_minh_chung,
                    'ngay_nop'        => $item->created_at->format('d/m/Y H:i'),
                    // Cấp Temporary Signed URL có thời hạn 30 phút nhằm bảo mật ảnh minh chứng PII
                    'url_xem_anh'     => URL::temporarySignedRoute(
                        'kyc.preview',
                        now()->addMinutes(30),
                        ['ma_xac_thuc' => $item->ma_yeu_cau_xac_thuc]
                    ),
                ];
            });
    }
}
