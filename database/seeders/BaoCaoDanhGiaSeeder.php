<?php

namespace Database\Seeders;

use App\Enums\TrangThaiDanhGiaMonHoc;
use App\Models\BaoCaoDanhGia;
use App\Models\DanhGiaMonHoc;
use App\Models\MonHoc;
use App\Models\NguoiDung;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BaoCaoDanhGiaSeeder extends Seeder
{
    public function run(): void
    {
        $courses = MonHoc::query()->take(4)->get();

        if ($courses->isEmpty()) {
            $this->command->warn('Không tìm thấy môn học nào.');
            return;
        }

        // 1. Lấy danh sách các tài khoản sinh viên đã có trong hệ thống
        $students = NguoiDung::query()
            ->where('vai_tro', 'SINH_VIEN')
            ->orderBy('ma_nguoi_dung')
            ->take(6)
            ->get();

        if ($students->count() < 3) {
            $this->command->warn('Cần ít nhất 3 tài khoản sinh viên trong CSDL.');
            return;
        }

        $studentIds = $students->pluck('ma_nguoi_dung')->toArray();

        $adminUser = NguoiDung::query()
            ->whereIn('vai_tro', ['QUAN_TRI_VIEN', 'KIEM_DUYET_VIEN'])
            ->first();

        // 2. Tạo một số bài đánh giá mẫu (Mock Reviews)
        $mockReviews = [
            // Bài 1: Ngôn từ xúc phạm / bôi nhọ giảng viên
            [
                'course'         => $courses[0],
                'author_id'      => $studentIds[0],
                'hoc_ky'         => '2025-1',
                'diem_do_kho'    => 5,
                'diem_thuc_tien' => 1,
                'diem_khoi_luong'=> 5,
                'noi_dung'       => 'Giảng viên bộ môn này dạy cực kỳ khinh thường sinh viên, chấm điểm bất công và thiên vị người quen. Đừng ai dại dột đăng ký học lớp này!',
                'an_danh'        => true,
                'trang_thai'     => TrangThaiDanhGiaMonHoc::DA_DUYET->value,
            ],
            // Bài 2: Spam quảng cáo đồ án thuê
            [
                'course'         => $courses[1] ?? $courses[0],
                'author_id'      => $studentIds[1],
                'hoc_ky'         => '2025-2',
                'diem_do_kho'    => 1,
                'diem_thuc_tien' => 5,
                'diem_khoi_luong'=> 1,
                'noi_dung'       => 'Ai lười làm đồ án môn này liên hệ Zalo 0987654321 nhận làm thuê bao đậu 100% điểm A+, cam kết bảo mật uy tín giá sinh viên!',
                'an_danh'        => false,
                'trang_thai'     => TrangThaiDanhGiaMonHoc::DA_DUYET->value,
            ],
            // Bài 3: Công kích quấy rối cá nhân
            [
                'course'         => $courses[2] ?? $courses[0],
                'author_id'      => $studentIds[2],
                'hoc_ky'         => '2024-2',
                'diem_do_kho'    => 4,
                'diem_thuc_tien' => 3,
                'diem_khoi_luong'=> 4,
                'noi_dung'       => 'Mấy bạn nữ nhóm 3 lớp sáng thứ 4 làm việc vô trách nhiệm, học dốt mà suốt ngày tranh quyền, nhìn mặt phát ghét chỉ muốn đấm.',
                'an_danh'        => false,
                'trang_thai'     => TrangThaiDanhGiaMonHoc::DA_DUYET->value,
            ],
            // Bài 4: Bôi nhọ quy mô khoa
            [
                'course'         => $courses[3] ?? $courses[0],
                'author_id'      => $studentIds[0],
                'hoc_ky'         => '2024-1',
                'diem_do_kho'    => 3,
                'diem_thuc_tien' => 2,
                'diem_khoi_luong'=> 4,
                'noi_dung'       => 'Chương trình đào tạo lạc hậu, giáo trình cũ rích từ thế kỷ trước, học xong ra trường chỉ có thất nghiệp bốc vác.',
                'an_danh'        => true,
                'trang_thai'     => TrangThaiDanhGiaMonHoc::DA_DUYET->value,
            ],
            // Bài 5: Đánh giá đã được xử lý trước đó (đang tạm ẩn)
            [
                'course'         => $courses[0],
                'author_id'      => $studentIds[1],
                'hoc_ky'         => '2023-2',
                'diem_do_kho'    => 5,
                'diem_thuc_tien' => 1,
                'diem_khoi_luong'=> 5,
                'noi_dung'       => 'Nhận xét này chứa liên kết ngoài nghi vấn mã độc: http://malicious-link-demo.com/tai-lieu-mien-phi',
                'an_danh'        => true,
                'trang_thai'     => TrangThaiDanhGiaMonHoc::TAM_AN->value,
            ],
        ];

        $createdReviews = [];
        foreach ($mockReviews as $m) {
            $createdReviews[] = DanhGiaMonHoc::withTrashed()->updateOrCreate(
                [
                    'ma_mon_hoc'    => $m['course']->ma_mon_hoc,
                    'ma_nguoi_dung' => $m['author_id'],
                    'hoc_ky'        => $m['hoc_ky'],
                ],
                [
                    'diem_do_kho'     => $m['diem_do_kho'],
                    'diem_thuc_tien'  => $m['diem_thuc_tien'],
                    'diem_khoi_luong' => $m['diem_khoi_luong'],
                    'noi_dung'        => $m['noi_dung'],
                    'an_danh'         => $m['an_danh'],
                    'trang_thai'      => $m['trang_thai'],
                    'ngay_xoa_tam'    => null,
                ]
            );
        }

        $reporterA = $studentIds[1];
        $reporterB = $studentIds[2] ?? $studentIds[0];
        $reporterC = $studentIds[0];
        $reporterD = $studentIds[3] ?? $studentIds[1];

        // 3. Tạo các bản ghi Báo cáo đánh giá (BaoCaoDanhGia)
        $reports = [
            // Báo cáo 1: CHỜ XỬ LÝ - Bôi nhọ xúc phạm giảng viên
            [
                'ma_danh_gia'      => $createdReviews[0]->ma_danh_gia,
                'loai_doi_tuong'   => 'GIANG_VIEN',
                'ten_giang_vien'   => 'TS. Lê Hoàng Nam',
                'ma_nguoi_bao_cao' => $reporterA,
                'ly_do'            => 'NGON_TU_KICH_DONG',
                'chi_tiet'         => 'Bài đánh giá sử dụng từ ngữ xúc phạm danh dự giảng viên, quy chụp thiếu văn hóa.',
                'trang_thai'       => 'CHO_XU_LY',
                'created_at'       => now()->subHours(2),
            ],
            // Báo cáo 2: CHỜ XỬ LÝ - Báo cáo 1 cho bài Spam đồ án thuê
            [
                'ma_danh_gia'      => $createdReviews[1]->ma_danh_gia,
                'loai_doi_tuong'   => 'MON_HOC',
                'ten_giang_vien'   => null,
                'ma_nguoi_bao_cao' => $reporterC,
                'ly_do'            => 'SPAM',
                'chi_tiet'         => 'Rao vặt dịch vụ làm thuê đồ án, quảng cáo thương mại trái phép trong cổng học thuật.',
                'trang_thai'       => 'CHO_XU_LY',
                'created_at'       => now()->subHours(5),
            ],
            // Báo cáo 3: CHỜ XỬ LÝ - Báo cáo 2 từ sinh viên khác cùng nhắm vào bài Spam (kiểm tra tính năng related reports)
            [
                'ma_danh_gia'      => $createdReviews[1]->ma_danh_gia,
                'loai_doi_tuong'   => 'MON_HOC',
                'ten_giang_vien'   => null,
                'ma_nguoi_bao_cao' => $reporterB,
                'ly_do'            => 'SPAM',
                'chi_tiet'         => 'Gian lận học thuật, đề nghị Ban kiểm duyệt gỡ bỏ ngay lập tức và khóa tài khoản.',
                'trang_thai'       => 'CHO_XU_LY',
                'created_at'       => now()->subHours(4),
            ],
            // Báo cáo 4: CHỜ XỬ LÝ - Quấy rối sinh viên khác
            [
                'ma_danh_gia'      => $createdReviews[2]->ma_danh_gia,
                'loai_doi_tuong'   => 'MON_HOC',
                'ten_giang_vien'   => null,
                'ma_nguoi_bao_cao' => $reporterD,
                'ly_do'            => 'QUAY_ROI',
                'chi_tiet'         => 'Công kích trực tiếp vào các bạn sinh viên nữ trong nhóm học, đe dọa bạn học.',
                'trang_thai'       => 'CHO_XU_LY',
                'created_at'       => now()->subHours(8),
            ],
            // Báo cáo 5: CHỜ XỬ LÝ - Bôi nhọ uy tín khoa
            [
                'ma_danh_gia'      => $createdReviews[3]->ma_danh_gia,
                'loai_doi_tuong'   => 'MON_HOC',
                'ten_giang_vien'   => null,
                'ma_nguoi_bao_cao' => $reporterB,
                'ly_do'            => 'BOI_NHO',
                'chi_tiet'         => 'Đánh giá mang tính tiêu cực cực đoan, hạ thấp uy tín chất lượng đào tạo của khoa.',
                'trang_thai'       => 'CHO_XU_LY',
                'created_at'       => now()->subDays(1),
            ],
            // Báo cáo 6: ĐÃ XỬ LÝ - Đã duyệt ẩn trước đó
            [
                'ma_danh_gia'      => $createdReviews[4]->ma_danh_gia,
                'loai_doi_tuong'   => 'MON_HOC',
                'ten_giang_vien'   => null,
                'ma_nguoi_bao_cao' => $reporterB,
                'ly_do'            => 'KHAC',
                'chi_tiet'         => 'Nghi vấn chứa đường link giả mạo lừa đảo sinh viên.',
                'trang_thai'       => 'DA_XU_LY',
                'nguoi_xu_ly'      => $adminUser?->ma_nguoi_dung,
                'thoi_gian_xu_ly'  => now()->subDays(2),
                'ghi_chu_xu_ly'    => 'Đã xác minh link độc hại, quyết định tạm ẩn bài viết và cảnh cáo sinh viên.',
                'created_at'       => now()->subDays(3),
            ],
            // Báo cáo 7: BÁC BỎ - Báo cáo sai đối với giảng viên
            [
                'ma_danh_gia'      => $createdReviews[0]->ma_danh_gia,
                'loai_doi_tuong'   => 'GIANG_VIEN',
                'ten_giang_vien'   => 'ThS. Trần Thị Mai',
                'ma_nguoi_bao_cao' => $reporterD,
                'ly_do'            => 'BOI_NHO',
                'chi_tiet'         => 'Em thấy bạn này chê đề thi khó quá nên muốn báo cáo.',
                'trang_thai'       => 'BAC_BO',
                'nguoi_xu_ly'      => $adminUser?->ma_nguoi_dung,
                'thoi_gian_xu_ly'  => now()->subDays(1),
                'ghi_chu_xu_ly'    => 'Nhận xét độ khó là ý kiến cá nhân hợp lệ, không vi phạm tiêu chuẩn cộng đồng.',
                'created_at'       => now()->subDays(2),
            ],
        ];

        foreach ($reports as $rep) {
            BaoCaoDanhGia::withTrashed()->updateOrCreate(
                [
                    'ma_danh_gia'      => $rep['ma_danh_gia'],
                    'ma_nguoi_bao_cao' => $rep['ma_nguoi_bao_cao'],
                ],
                $rep + ['deleted_at' => null]
            );
        }

        $this->command->info('Đã tạo thành công ' . count($reports) . ' bản ghi mock data báo cáo đánh giá!');
    }
}
