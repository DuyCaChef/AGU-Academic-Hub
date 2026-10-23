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

        // Kiểm tra xem người đang đăng nhập có phải là tác giả của đánh giá này không
        $isAuthor = $currentUser && ($currentUser->ma_nguoi_dung === $this->ma_nguoi_danh_gia);

        // Tính điểm trung bình cộng 3 tiêu chí
        $diemTrungBinh = round(($this->diem_do_kho + $this->diem_thuc_tien + $this->diem_ap_luc_bai_tap) / 3, 1);

        return [
            'ma_danh_gia'         => $this->ma_danh_gia,
            'hoc_ky'              => $this->hoc_ky,
            'diem_do_kho'         => $this->diem_do_kho,
            'diem_thuc_tien'      => $this->diem_thuc_tien,
            'diem_ap_luc_bai_tap' => $this->diem_ap_luc_bai_tap,
            'diem_trung_binh'     => $diemTrungBinh,
            'nhan_xet'            => $this->nhan_xet,
            'la_an_danh'          => (bool) $this->la_an_danh,
            'ngay_tao'            => $this->created_at?->format('d/m/Y'),
            'ngay_cap_nhat'       => $this->updated_at?->format('d/m/Y'),

            // Cờ cho phép giao diện hiển thị nút Sửa/Xóa (chỉ tác giả mới thấy)
            'co_quyen_chinh_sua'  => $isAuthor,

            // ẨN DANH 2 LỚP: Tuyệt đối không để lộ ma_nguoi_danh_gia ra ngoài
            'tac_gia'             => $this->resolveTacGiaPayload($isAuthor),
        ];
    }

    // Tách logic hiển thị tác giả tùy thuộc vào cờ la_an_danh
    private function resolveTacGiaPayload(bool $isAuthor): array
    {
        // 1. Trường hợp người dùng chọn ẨN DANH
        if ($this->la_an_danh) {
            return [
                'ten_hien_thi' => 'Sinh viên ẩn danh',
                'anh_dai_dien' => null,
                'la_an_danh'   => true,
                // Riêng chính tác giả xem lại bài của mình thì hiển thị ghi chú nhắc nhở
                'ghi_chu'      => $isAuthor ? '(Bài viết của bạn - Đang hiển thị ẩn danh với cộng đồng)' : null,
            ];
        }

        // 2. Trường hợp CÔNG KHAI danh tính
        // Vẫn bảo vệ quyền riêng tư: chỉ xuất ho_ten và ten_khoa, KHÔNG xuất mssv hay ma_nguoi_dung
        return [
            'ten_hien_thi' => $this->nguoiDung?->ho_ten ?? 'Thành viên AGU',
            'anh_dai_dien' => $this->nguoiDung?->anh_dai_dien,
            'ten_khoa'     => $this->nguoiDung?->khoa?->ten_khoa,
            'la_an_danh'   => false,
            'ghi_chu'      => $isAuthor ? '(Bài viết của bạn)' : null,
        ];
    }
}