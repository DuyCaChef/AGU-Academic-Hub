<?php

namespace App\Modules\Bounties\Application\Queries;

use App\Enums\LoaiGiaoDichDiem;
use App\Models\NguoiDung;
use App\Models\ViDiem;

// Query lấy dữ liệu tổng hợp số dư ví điểm và lịch sử biến động giao dịch
class GetWalletDataQuery
{
    public function execute(NguoiDung $user, array $filters = []): array
    {
        // 1. Lấy hoặc tạo ví điểm mặc định nếu chưa tồn tại
        $wallet = $user->viDiem ?: ViDiem::firstOrCreate(
            ['ma_nguoi_dung' => $user->ma_nguoi_dung],
            [
                'diem_kha_dung' => 100,
                'diem_tam_giu'  => 0,
            ]
        );

        // 2. Truy vấn danh sách giao dịch điểm
        $query = $wallet->giaoDichs()->orderByDesc('thoi_gian_tao')->orderByDesc('ma_giao_dich');

        $typeFilter = $filters['type'] ?? 'ALL';

        if ($typeFilter === 'REWARD') {
            $query->whereIn('loai_giao_dich', [
                LoaiGiaoDichDiem::CONG_DIEM->value,
                LoaiGiaoDichDiem::HOAN_TRA->value,
            ]);
        } elseif ($typeFilter === 'FROZEN') {
            $query->whereIn('loai_giao_dich', [
                LoaiGiaoDichDiem::TAM_GIU->value,
                LoaiGiaoDichDiem::SU_DUNG->value,
            ]);
        } elseif ($typeFilter !== 'ALL' && LoaiGiaoDichDiem::tryFrom($typeFilter)) {
            $query->where('loai_giao_dich', $typeFilter);
        }

        $transactions = $query->paginate(15)->withQueryString();

        // 3. Chuẩn hóa dữ liệu trả về cho Frontend
        $transactions->through(function ($tx) {
            $loaiEnum = is_object($tx->loai_giao_dich)
                ? $tx->loai_giao_dich
                : LoaiGiaoDichDiem::tryFrom((string) $tx->loai_giao_dich);

            $loaiStr = $loaiEnum ? $loaiEnum->value : (string) $tx->loai_giao_dich;
            $tenLoai = $loaiEnum ? $loaiEnum->label() : 'Biến động ví';

            $laTangDiem = $tx->thay_doi_kha_dung > 0;
            $laGiamDiem = $tx->thay_doi_kha_dung < 0;

            return [
                'ma_giao_dich'       => $tx->ma_giao_dich,
                'loai_giao_dich'     => $loaiStr,
                'ten_loai_giao_dich' => $tenLoai,
                'so_diem'            => $tx->so_diem,
                'thay_doi_kha_dung'  => $tx->thay_doi_kha_dung,
                'thay_doi_tam_giu'   => $tx->thay_doi_tam_giu,
                'la_tang_diem'       => $laTangDiem,
                'la_giam_diem'       => $laGiamDiem,
                'mo_ta'              => $tx->mo_ta,
                'thoi_gian_tao'      => $tx->thoi_gian_tao?->toISOString(),
                'created_at'         => $tx->thoi_gian_tao?->toISOString(),
            ];
        });

        return [
            'wallet' => [
                'diem_kha_dung' => (int) $wallet->diem_kha_dung,
                'diem_tam_giu'  => (int) $wallet->diem_tam_giu,
                'tong_diem'     => (int) ($wallet->diem_kha_dung + $wallet->diem_tam_giu),
                'diem_uy_tin'   => (int) ($user->diem_uy_tin ?? 100),
            ],
            'transactions' => $transactions,
            'filters'      => [
                'type' => $typeFilter,
            ],
        ];
    }
}

