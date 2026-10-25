<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseReviewResource extends JsonResource
{
    // Chuyển đổi đối tượng DanhGiaMonHoc sang định dạng hiển thị công khai.
    // Áp dụng nguyên tắc ẨN DANH 2 LỚP: Tuyệt đối che giấu ma_nguoi_danh_gia
    public function toArray(Request $request): array
    {
        $currentUser = $request->user();
        $userId = $currentUser ? ($currentUser->ma_nguoi_dung ?? $currentUser->getKey()) : null;

        // Kiểm tra xem người đang đăng nhập có phải là tác giả của đánh giá này không
        $isAuthor = $userId && ($userId === ($this->ma_nguoi_dung ?? $this->ma_nguoi_danh_gia));

        $workloadScore = $this->diem_khoi_luong ?? $this->diem_ap_luc_bai_tap ?? 0;
        $content = $this->noi_dung ?? $this->nhan_xet ?? '';
        $isAnon = (bool) ($this->an_danh ?? $this->la_an_danh);

        // Tính điểm trung bình cộng 3 tiêu chí
        $diemTrungBinh = round(($this->diem_do_kho + $this->diem_thuc_tien + $workloadScore) / 3, 1);

        $votedByMe = false;
        if ($userId) {
            if ($this->relationLoaded('binhChons')) {
                $votedByMe = $this->binhChons->contains('ma_nguoi_dung', $userId);
            } else {
                $votedByMe = $this->binhChons()->where('ma_nguoi_dung', $userId)->exists();
            }
        }

        $helpfulVotesCount = $this->binh_chons_count ?? ($this->relationLoaded('binhChons') ? $this->binhChons->count() : $this->binhChons()->count());

        $authorInfo = $this->xayDungThongTinTacGia($isAuthor, $isAnon);

        return [
            'ma_danh_gia'         => $this->ma_danh_gia,
            'id'                  => $this->ma_danh_gia,
            'hoc_ky'              => $this->hoc_ky,
            'academic_term'       => $this->hoc_ky,
            'diem_do_kho'         => $this->diem_do_kho,
            'difficulty_score'    => $this->diem_do_kho,
            'diem_thuc_tien'      => $this->diem_thuc_tien,
            'practicality_score'  => $this->diem_thuc_tien,
            'diem_khoi_luong'     => $workloadScore,
            'diem_ap_luc_bai_tap' => $workloadScore,
            'workload_score'      => $workloadScore,
            'diem_trung_binh'     => $diemTrungBinh,
            'noi_dung'            => $content,
            'nhan_xet'            => $content,
            'content'             => $content,
            'an_danh'             => $isAnon,
            'la_an_danh'          => $isAnon,
            'is_anonymous'        => $isAnon,
            'ngay_tao'            => $this->created_at?->format('d/m/Y'),
            'created_at'          => $this->created_at?->format('d/m/Y'),
            'ngay_cap_nhat'       => $this->updated_at?->format('d/m/Y'),
            'so_luot_huu_ich'     => $helpfulVotesCount,
            'helpful_votes_count' => $helpfulVotesCount,
            'is_voted_by_me'      => $votedByMe,

            // Cờ cho phép giao diện hiển thị nút Sửa/Xóa (chỉ tác giả mới thấy)
            'co_quyen_chinh_sua'  => $isAuthor,
            'can_modify'          => $isAuthor,

            // ẨN DANH 2 LỚP: Tuyệt đối không để lộ ma_nguoi_dung ra ngoài
            'tac_gia'             => $authorInfo,
            'author'              => $authorInfo,
        ];
    }

    // Xây dựng thông tin hiển thị tác giả tuỳ thuộc vào cờ an_danh
    private function xayDungThongTinTacGia(bool $isAuthor, bool $isAnon): array
    {
        // 1. Trường hợp người dùng chọn ẨN DANH
        if ($isAnon) {
            return [
                'ten_hien_thi' => 'Sinh viên ẩn danh',
                'display_name' => 'Sinh viên ẩn danh',
                'anh_dai_dien' => null,
                'avatar'       => null,
                'la_an_danh'   => true,
                'is_anonymous' => true,
                // Riêng chính tác giả xem lại bài của mình thì hiển thị ghi chú nhắc nhở
                'ghi_chu'      => $isAuthor ? '(Bài viết của bạn - Đang hiển thị ẩn danh với cộng đồng)' : null,
                'note'         => $isAuthor ? '(Bài viết của bạn - Đang hiển thị ẩn danh với cộng đồng)' : null,
            ];
        }

        // 2. Trường hợp CÔNG KHAI danh tính
        // Vẫn bảo vệ quyền riêng tư: chỉ xuất ho_ten và ten_khoa, KHÔNG xuất mssv hay ma_nguoi_dung
        $displayName = $this->nguoiDung?->ho_ten ?? 'Thành viên AGU';
        $departmentName = $this->nguoiDung?->khoa?->ten_khoa;

        return [
            'ten_hien_thi' => $displayName,
            'display_name' => $displayName,
            'anh_dai_dien' => $this->nguoiDung?->anh_dai_dien,
            'avatar'       => $this->nguoiDung?->anh_dai_dien,
            'ten_khoa'     => $departmentName,
            'department'   => $departmentName,
            'la_an_danh'   => false,
            'is_anonymous' => false,
            'ghi_chu'      => $isAuthor ? '(Bài viết của bạn)' : null,
            'note'         => $isAuthor ? '(Bài viết của bạn)' : null,
        ];
    }
}