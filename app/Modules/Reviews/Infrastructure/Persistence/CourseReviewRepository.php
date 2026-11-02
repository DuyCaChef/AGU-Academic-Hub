<?php

namespace App\Modules\Reviews\Infrastructure\Persistence;

use App\Models\BaoCaoDanhGia;
use App\Models\BinhChonDanhGia;
use App\Models\DanhGiaMonHoc;
use App\Models\MonHoc;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

// Repository xử lý truy vấn dữ liệu đánh giá môn học, thống kê chỉ số và tương tác bình chọn
class CourseReviewRepository
{
    // Tìm môn học theo mã học phần, yêu cầu Khoa phải đang hiệu lực
    public function findCourseByCode(string $maHocPhan): MonHoc
    {
        return MonHoc::where('ma_hoc_phan', $maHocPhan)
            ->whereHas('khoa')
            ->firstOrFail();
    }

    // Tìm bài đánh giá theo ID
    public function findReviewById(int $maDanhGia): DanhGiaMonHoc
    {
        return DanhGiaMonHoc::findOrFail($maDanhGia);
    }

    // Tính toán thống kê điểm trung bình 3 tiêu chí của môn học
    public function getCourseReviewStats(int $courseId): array
    {
        $stats = DanhGiaMonHoc::where('ma_mon_hoc', $courseId)
            ->selectRaw('
                COUNT(*) as tong_so_danh_gia,
                AVG(diem_do_kho) as avg_do_kho,
                AVG(diem_thuc_tien) as avg_thuc_tien,
                AVG(diem_khoi_luong) as avg_khoi_luong,
                AVG(diem_khoi_luong) as avg_ap_luc
            ')
            ->first();

        $tongSo = (int) ($stats->tong_so_danh_gia ?? 0);
        $avgDoKho = round((float) ($stats->avg_do_kho ?? 0), 1);
        $avgThucTien = round((float) ($stats->avg_thuc_tien ?? 0), 1);
        $avgKhoiLuong = round((float) ($stats->avg_khoi_luong ?? $stats->avg_ap_luc ?? 0), 1);
        $diemTongQuan = round(($avgDoKho + $avgThucTien + $avgKhoiLuong) / 3, 1);

        return [
            'tong_so_danh_gia' => $tongSo,
            'avg_do_kho'       => $avgDoKho,
            'avg_thuc_tien'    => $avgThucTien,
            'avg_ap_luc'       => $avgKhoiLuong,
            'avg_khoi_luong'   => $avgKhoiLuong,
            'diem_tong_quan'   => $diemTongQuan,
        ];
    }

    // Lấy danh sách đánh giá phân trang kèm thông tin người dùng và khoa
    public function getPaginatedReviews(int $courseId, int $perPage = 10): LengthAwarePaginator
    {
        return DanhGiaMonHoc::with(['nguoiDung.khoa'])
            ->withCount('binhChons')
            ->where('ma_mon_hoc', $courseId)
            ->orderBy('ma_danh_gia', 'desc')
            ->paginate($perPage);
    }

    // Lưu bài đánh giá mới
    public function createReview(array $data): DanhGiaMonHoc
    {
        return DanhGiaMonHoc::create($data);
    }

    // Cập nhật nội dung bài đánh giá
    public function updateReview(DanhGiaMonHoc $review, array $data): bool
    {
        return $review->update($data);
    }

    // Xóa mềm bài đánh giá
    public function deleteReview(DanhGiaMonHoc $review): bool
    {
        return (bool) $review->delete();
    }

    // Chuyển đổi trạng thái bình chọn hữu ích (vote/unvote)
    // Trả về true nếu đã vote, false nếu bỏ vote
    public function toggleHelpfulVote(int $reviewId, int $userId): bool
    {
        $daVote = BinhChonDanhGia::where('ma_danh_gia', $reviewId)
            ->where('ma_nguoi_dung', $userId)
            ->first();

        if ($daVote) {
            $daVote->delete();
            return false;
        }

        BinhChonDanhGia::create([
            'ma_danh_gia'   => $reviewId,
            'ma_nguoi_dung' => $userId,
            'huu_ich'       => true,
        ]);

        return true;
    }

    // Ghi nhận hoặc cập nhật báo cáo vi phạm
    public function reportReview(int $reviewId, int $reporterId, string $reason, ?string $details = null): BaoCaoDanhGia
    {
        return BaoCaoDanhGia::updateOrCreate(
            [
                'ma_danh_gia'      => $reviewId,
                'ma_nguoi_bao_cao' => $reporterId,
            ],
            [
                'ly_do'            => $reason,
                'chi_tiet'         => $details ?? $reason,
                'trang_thai'       => 'CHO_XU_LY',
            ]
        );
    }
}

