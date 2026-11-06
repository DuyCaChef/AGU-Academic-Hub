<?php

namespace App\Modules\Reviews\Infrastructure\Persistence;

use App\Enums\TrangThaiDanhGiaMonHoc;
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
    public function findReviewById(int $maDanhGia, bool $withTrashed = false): DanhGiaMonHoc
    {
        $query = DanhGiaMonHoc::query();
        if ($withTrashed) {
            $query->withTrashed();
        }
        return $query->findOrFail($maDanhGia);
    }

    // Tính toán thống kê điểm trung bình 3 tiêu chí của môn học
    public function getCourseReviewStats(int $courseId): array
    {
        $stats = DanhGiaMonHoc::daDuyet()
            ->where('ma_mon_hoc', $courseId)
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

    // Lấy danh sách đánh giá phân trang kèm thông tin người dùng và thống kê bình chọn
    public function getPaginatedReviews(int $courseId, int $perPage = 10): LengthAwarePaginator
    {
        return DanhGiaMonHoc::daDuyet()
            ->with(['nguoiDung.khoa', 'binhChons'])
            ->withCount([
                'binhChons',
                'binhChons as upvotes_count' => function ($query) {
                    $query->where('loai_binh_chon', 1);
                },
                'binhChons as downvotes_count' => function ($query) {
                    $query->where('loai_binh_chon', -1);
                },
            ])
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

    // Thực hiện bình chọn cộng đồng (Upvote +1 / Downvote -1 / Hủy vote / Đổi hướng vote)
    public function castCommunityVote(int $reviewId, int $userId, int $voteType): array
    {
        $targetVote = $voteType === -1 ? -1 : 1;

        $existingVote = BinhChonDanhGia::where('ma_danh_gia', $reviewId)
            ->where('ma_nguoi_dung', $userId)
            ->first();

        $action = 'voted';
        $myVote = $targetVote;
        $message = $targetVote === 1 ? 'Đã ghi nhận Upvote.' : 'Đã ghi nhận Downvote.';

        if ($existingVote) {
            $currentVote = (int) ($existingVote->loai_binh_chon ?? ($existingVote->huu_ich ? 1 : -1));

            if ($currentVote === $targetVote) {
                // Nhấp lại cùng loại -> Hủy bình chọn
                $existingVote->delete();
                $action = 'cancelled';
                $myVote = null;
                $message = $targetVote === 1 ? 'Đã hủy Upvote.' : 'Đã hủy Downvote.';
            } else {
                // Khác loại -> Đổi hướng bình chọn
                $existingVote->update([
                    'loai_binh_chon' => $targetVote,
                    'huu_ich'        => ($targetVote === 1),
                ]);
                $action = 'switched';
                $myVote = $targetVote;
                $message = $targetVote === 1 ? 'Đã chuyển sang Upvote.' : 'Đã chuyển sang Downvote.';
            }
        } else {
            // Chưa vote -> Tạo mới
            BinhChonDanhGia::create([
                'ma_danh_gia'    => $reviewId,
                'ma_nguoi_dung'  => $userId,
                'loai_binh_chon' => $targetVote,
                'huu_ich'        => ($targetVote === 1),
            ]);
        }

        $scores = $this->getReviewVoteScores($reviewId);

        return [
            'status'          => 'success',
            'action'          => $action,
            'message'         => $message,
            'my_vote'         => $myVote,
            'review_score'    => $scores['review_score'],
            'upvotes_count'   => $scores['upvotes_count'],
            'downvotes_count' => $scores['downvotes_count'],
        ];
    }

    // Tính toán tổng điểm cộng đồng và số lượng Upvote / Downvote của 1 bài đánh giá
    public function getReviewVoteScores(int $reviewId): array
    {
        $upvotes = BinhChonDanhGia::where('ma_danh_gia', $reviewId)
            ->where('loai_binh_chon', 1)
            ->count();

        $downvotes = BinhChonDanhGia::where('ma_danh_gia', $reviewId)
            ->where('loai_binh_chon', -1)
            ->count();

        return [
            'upvotes_count'   => $upvotes,
            'downvotes_count' => $downvotes,
            'review_score'    => $upvotes - $downvotes,
        ];
    }

    // Chuyển đổi trạng thái bình chọn hữu ích (tương thích ngược với method cũ)
    public function toggleHelpfulVote(int $reviewId, int $userId): bool
    {
        $result = $this->castCommunityVote($reviewId, $userId, 1);
        return $result['action'] !== 'cancelled';
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
