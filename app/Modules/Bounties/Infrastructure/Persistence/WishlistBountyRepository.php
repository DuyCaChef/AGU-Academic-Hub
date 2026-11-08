<?php

namespace App\Modules\Bounties\Infrastructure\Persistence;

use App\Enums\TrangThaiYeuCauTaiLieu;
use App\Models\DapUngYeuCauTaiLieu;
use App\Models\MonHoc;
use App\Models\YeuCauTaiLieu;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

// Repository xử lý truy vấn dữ liệu Wishlist, bài nộp phản hồi và trạng thái yêu cầu
class WishlistBountyRepository
{
    // Lấy danh sách yêu cầu tìm tài liệu kèm bộ lọc và phân trang
    public function getPaginatedWishlist(array $filters, int $perPage = 12): LengthAwarePaginator
    {
        $query = YeuCauTaiLieu::whereHas('monHoc.khoa')->with([
            'monHoc:ma_mon_hoc,ma_hoc_phan,ten_mon_hoc',
            'nguoiTao:ma_nguoi_dung,ho_ten,mssv,anh_dai_dien,diem_uy_tin',
            'taiLieuDapUng:ma_tai_lieu,tieu_de',
        ])->withCount('dapUngs');

        // Lọc theo trạng thái (mặc định: MO)
        $trangThai = $filters['trang_thai'] ?? TrangThaiYeuCauTaiLieu::MO->value;
        if ($trangThai !== 'TAT_CA') {
            $query->where('trang_thai', $trangThai);
        }

        // Lọc theo môn học
        if (!empty($filters['ma_mon_hoc'])) {
            $query->where('ma_mon_hoc', $filters['ma_mon_hoc']);
        }

        // Lọc theo phân loại tài liệu
        if (!empty($filters['phan_loai'])) {
            $query->where('phan_loai', $filters['phan_loai']);
        }

        // Sắp xếp theo điểm thưởng hoặc mới nhất
        $sapXep = $filters['sap_xep'] ?? 'moi_nhat';
        if ($sapXep === 'diem_cao') {
            $query->orderByDesc('diem_thuong');
        } else {
            $query->orderByDesc('ma_yeu_cau');
        }

        return $query->paginate($perPage);
    }

    // Danh sách các môn học hợp lệ dùng cho bộ lọc
    public function getActiveCourses(): Collection
    {
        return MonHoc::whereHas('khoa')
            ->select('ma_mon_hoc', 'ma_hoc_phan', 'ten_mon_hoc')
            ->orderBy('ten_mon_hoc')
            ->get();
    }

    // Kiểm tra môn học có đang hiệu lực hay không
    public function isCourseActive(int $courseId): bool
    {
        return MonHoc::whereKey($courseId)->whereHas('khoa')->exists();
    }

    // Tìm chi tiết yêu cầu kèm thông tin liên kết
    public function findRequestById(int $maYeuCau): YeuCauTaiLieu
    {
        return YeuCauTaiLieu::with([
            'monHoc',
            'nguoiTao:ma_nguoi_dung,ho_ten,mssv,anh_dai_dien,diem_uy_tin',
            'taiLieuDapUng',
            'dapUngs.nguoiDongGop:ma_nguoi_dung,ho_ten,mssv,anh_dai_dien,diem_uy_tin',
            'dapUngs.taiLieu:ma_tai_lieu,tieu_de,duong_dan_tep,dinh_dang_tep,dung_luong_kb,phan_loai,luot_tai,trang_thai',
        ])->findOrFail($maYeuCau);
    }

    // Tạo bản ghi yêu cầu mới
    public function createRequest(array $data): YeuCauTaiLieu
    {
        return YeuCauTaiLieu::create($data);
    }

    // Kiểm tra xem tài liệu đã từng được gửi phản hồi cho yêu cầu này chưa
    public function submissionExists(int $maYeuCau, int $maTaiLieu): bool
    {
        return DapUngYeuCauTaiLieu::where('ma_yeu_cau', $maYeuCau)
            ->where('ma_tai_lieu', $maTaiLieu)
            ->exists();
    }

    // Tạo bài nộp phản hồi đóng góp tài liệu
    public function createSubmission(array $data): DapUngYeuCauTaiLieu
    {
        return DapUngYeuCauTaiLieu::create($data);
    }

    // Tìm bài nộp phản hồi cụ thể theo yêu cầu
    public function findSubmission(int $maYeuCau, int $maDapUng): DapUngYeuCauTaiLieu
    {
        return DapUngYeuCauTaiLieu::with(['nguoiDongGop', 'taiLieu'])
            ->where('ma_yeu_cau', $maYeuCau)
            ->findOrFail($maDapUng);
    }

    // Cập nhật trạng thái khi xác nhận giải ngân thành công
    public function updateConfirmedFulfillment(
        YeuCauTaiLieu $yeuCau,
        DapUngYeuCauTaiLieu $dapUng,
        int $approverId
    ): void {
        // Cập nhật bản đáp ứng được chọn
        $dapUng->update([
            'trang_thai'      => 'CHAP_NHAN',
            'nguoi_duyet'     => $approverId,
            'thoi_gian_duyet' => now(),
        ]);

        // Từ chối các bản đáp ứng khác chưa được chọn
        DapUngYeuCauTaiLieu::where('ma_yeu_cau', $yeuCau->ma_yeu_cau)
            ->where('ma_dap_ung', '!=', $dapUng->ma_dap_ung)
            ->where('trang_thai', 'CHO_DUYET')
            ->update(['trang_thai' => 'TU_CHOI']);

        // Đóng yêu cầu sang DA_HOAN_THANH
        $yeuCau->update([
            'trang_thai'          => TrangThaiYeuCauTaiLieu::DA_HOAN_THANH,
            'ma_tai_lieu_dap_ung' => $dapUng->ma_tai_lieu,
            'nguoi_xac_nhan'      => $approverId,
            'thoi_gian_xac_nhan'  => now(),
        ]);
    }

    // Cập nhật trạng thái hủy và soft delete
    public function cancelRequest(YeuCauTaiLieu $yeuCau): void
    {
        $yeuCau->update([
            'trang_thai' => TrangThaiYeuCauTaiLieu::DA_HUY,
        ]);

        $yeuCau->delete();
    }

    // Lấy danh sách yêu cầu đang chờ duyệt giải ngân phân trang cho Admin/Mod
    public function getPendingPayoutQueue(int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return YeuCauTaiLieu::with([
            'monHoc:ma_mon_hoc,ma_hoc_phan,ten_mon_hoc',
            'nguoiTao:ma_nguoi_dung,ho_ten,mssv,anh_dai_dien',
            'dapUngDuocChon.nguoiDongGop:ma_nguoi_dung,ho_ten,mssv,anh_dai_dien',
            'dapUngDuocChon.taiLieu:ma_tai_lieu,tieu_de,duong_dan_tep,dinh_dang_tep,dung_luong_kb,phan_loai',
        ])
            ->where('trang_thai', \App\Enums\TrangThaiYeuCauTaiLieu::CHO_GIAI_NGAN)
            ->orderByDesc('thoi_gian_nguoi_yeu_cau_xac_nhan')
            ->paginate($perPage);
    }

    // Lấy chi tiết yêu cầu chờ duyệt giải ngân kèm đầy đủ thông tin liên kết
    public function getPendingPayoutDetail(int $maYeuCau): YeuCauTaiLieu
    {
        return YeuCauTaiLieu::with([
            'monHoc',
            'nguoiTao:ma_nguoi_dung,ho_ten,mssv,anh_dai_dien,diem_uy_tin',
            'dapUngDuocChon.nguoiDongGop:ma_nguoi_dung,ho_ten,mssv,anh_dai_dien,diem_uy_tin',
            'dapUngDuocChon.taiLieu',
            'dapUngs.nguoiDongGop:ma_nguoi_dung,ho_ten,mssv',
            'dapUngs.taiLieu:ma_tai_lieu,tieu_de,trang_thai',
        ])
            ->where('trang_thai', \App\Enums\TrangThaiYeuCauTaiLieu::CHO_GIAI_NGAN)
            ->findOrFail($maYeuCau);
    }

    // Cập nhật trạng thái khi Admin/Mod duyệt giải ngân thành công
    public function approvePayout(YeuCauTaiLieu $yeuCau, DapUngYeuCauTaiLieu $dapUng, int $actorId): void
    {
        // Chuyển bài nộp được chọn sang CHAP_NHAN
        $dapUng->update([
            'trang_thai'      => 'CHAP_NHAN',
            'nguoi_duyet'     => $actorId,
            'thoi_gian_duyet' => now(),
        ]);

        // Từ chối tất cả bài nộp khác còn lại
        DapUngYeuCauTaiLieu::where('ma_yeu_cau', $yeuCau->ma_yeu_cau)
            ->where('ma_dap_ung', '!=', $dapUng->ma_dap_ung)
            ->whereIn('trang_thai', ['CHO_DUYET', 'NGUOI_YEU_CAU_XAC_NHAN'])
            ->update(['trang_thai' => 'TU_CHOI']);

        // Đóng yêu cầu với trạng thái DA_HOAN_THANH
        $yeuCau->update([
            'trang_thai'          => \App\Enums\TrangThaiYeuCauTaiLieu::DA_HOAN_THANH,
            'ma_tai_lieu_dap_ung' => $dapUng->ma_tai_lieu,
            'nguoi_xac_nhan'      => $actorId,
            'thoi_gian_xac_nhan'  => now(),
        ]);
    }

    // Cập nhật trạng thái khi Admin/Mod từ chối giải ngân - mở lại yêu cầu để nhận bài nộp mới
    public function rejectPayout(YeuCauTaiLieu $yeuCau, DapUngYeuCauTaiLieu $dapUng, string $lyDo, int $actorId): void
    {
        // Đánh dấu bài nộp bị từ chối kèm lý do
        $dapUng->update([
            'trang_thai'              => 'TU_CHOI',
            'ly_do_tu_choi_giai_ngan' => $lyDo,
            'nguoi_duyet'             => $actorId,
            'thoi_gian_duyet'         => now(),
        ]);

        // Mở lại yêu cầu sang trạng thái MO để sinh viên khác có thể nộp bài
        $yeuCau->update([
            'trang_thai'                       => \App\Enums\TrangThaiYeuCauTaiLieu::MO,
            'ma_dap_ung_duoc_chon'             => null,
            'thoi_gian_nguoi_yeu_cau_xac_nhan' => null,
        ]);
    }
}

