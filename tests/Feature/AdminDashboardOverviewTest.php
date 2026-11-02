<?php

namespace Tests\Feature;

use App\Enums\CapQuanTri;
use App\Enums\LoaiXacThuc;
use App\Enums\TrangThaiNguoiDung;
use App\Enums\TrangThaiXacThuc;
use App\Enums\VaiTroNguoiDung;
use App\Models\BaoCaoViPham;
use App\Models\ChuyenNganh;
use App\Models\DanhSachSinhVienRaw;
use App\Models\Khoa;
use App\Models\MonHoc;
use App\Models\NguoiDung;
use App\Models\TaiLieu;
use App\Models\YeuCauGoBoTaiLieu;
use App\Models\YeuCauXacThuc;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminDashboardOverviewTest extends TestCase
{
    use RefreshDatabase;

    // Khách chưa đăng nhập không thể truy cập dashboard quản trị
    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('login'));
    }

    // Sinh viên bình thường bị cấm truy cập dashboard quản trị (403 Forbidden)
    public function test_regular_student_cannot_access_admin_dashboard(): void
    {
        $student = NguoiDung::factory()->create([
            'vai_tro' => VaiTroNguoiDung::SINH_VIEN,
        ]);

        $this->actingAs($student)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    // Kiểm duyệt viên có thể truy cập dashboard quản trị
    public function test_moderator_can_access_admin_dashboard(): void
    {
        $moderator = NguoiDung::factory()->create([
            'vai_tro' => VaiTroNguoiDung::KIEM_DUYET_VIEN,
            'trang_thai' => TrangThaiNguoiDung::DANG_HOAT_DONG,
        ]);

        $this->actingAs($moderator)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard/Index')
                ->has('stats')
            );
    }

    // Quản trị viên truy cập Dashboard với đầy đủ 14 chỉ số hệ thống theo yêu cầu
    public function test_admin_dashboard_provides_all_comprehensive_metrics(): void
    {
        $admin = NguoiDung::factory()->create([
            'vai_tro' => VaiTroNguoiDung::QUAN_TRI_VIEN,
            'cap_quan_tri' => CapQuanTri::TOI_CAO,
            'trang_thai' => TrangThaiNguoiDung::DANG_HOAT_DONG,
        ]);

        // 1. Tạo người dùng: Hoạt động, Tạm khóa, Chưa xác thực
        NguoiDung::factory()->create([
            'trang_thai' => TrangThaiNguoiDung::DANG_HOAT_DONG,
            'vai_tro' => VaiTroNguoiDung::SINH_VIEN,
        ]);
        NguoiDung::factory()->create([
            'trang_thai' => TrangThaiNguoiDung::TAM_KHOA,
            'vai_tro' => VaiTroNguoiDung::CUU_SINH_VIEN,
            'so_lan_vi_pham' => 3,
        ]);

        // 2. Dữ liệu thô sinh viên (Raw roster): Đã kích hoạt và Chưa kích hoạt
        DanhSachSinhVienRaw::create([
            'mssv' => 'B2000001',
            'ho_ten' => 'Sinh Viên Đã Kích Hoạt',
            'email_sv' => 'b2000001@student.agu.edu.vn',
            'mat_khau_mac_dinh' => bcrypt('password123'),
            'da_kich_hoat' => true,
            'kich_hoat_luc' => now(),
        ]);
        DanhSachSinhVienRaw::create([
            'mssv' => 'B2000002',
            'ho_ten' => 'Sinh Viên Chưa Đăng Nhập',
            'email_sv' => 'b2000002@student.agu.edu.vn',
            'mat_khau_mac_dinh' => bcrypt('password123'),
            'da_kich_hoat' => false,
        ]);

        // 3. Hồ sơ KYC: Cựu SV chờ duyệt, Đã duyệt, Bị từ chối
        YeuCauXacThuc::create([
            'ma_nguoi_dung' => $admin->ma_nguoi_dung,
            'loai_xac_thuc' => LoaiXacThuc::CUU_SINH_VIEN,
            'mssv' => 'ALUMNI01',
            'nam_tot_nghiep' => 2022,
            'duong_dan_minh_chung' => 'kyc/sample1.jpg',
            'loai_tep' => 'image/jpeg',
            'trang_thai' => TrangThaiXacThuc::CHO_DUYET,
        ]);
        YeuCauXacThuc::create([
            'ma_nguoi_dung' => $admin->ma_nguoi_dung,
            'loai_xac_thuc' => LoaiXacThuc::CUU_SINH_VIEN,
            'mssv' => 'ALUMNI02',
            'nam_tot_nghiep' => 2021,
            'duong_dan_minh_chung' => 'kyc/sample2.jpg',
            'loai_tep' => 'image/jpeg',
            'trang_thai' => TrangThaiXacThuc::DA_DUYET,
        ]);
        YeuCauXacThuc::create([
            'ma_nguoi_dung' => $admin->ma_nguoi_dung,
            'loai_xac_thuc' => LoaiXacThuc::CUU_SINH_VIEN,
            'mssv' => 'ALUMNI03',
            'nam_tot_nghiep' => 2020,
            'duong_dan_minh_chung' => 'kyc/sample3.jpg',
            'loai_tep' => 'image/jpeg',
            'trang_thai' => TrangThaiXacThuc::TU_CHOI,
        ]);

        // 4. Danh mục học thuật: Khoa, Chuyên ngành, Môn học
        $khoa = Khoa::create(['ky_hieu_khoa' => 'CNTT', 'ten_khoa' => 'Công nghệ Thông tin']);
        $chuyenNganh = ChuyenNganh::create([
            'ma_khoa' => $khoa->ma_khoa,
            'ky_hieu_nganh' => 'KTPM',
            'ten_chuyen_nganh' => 'Kỹ thuật Phần mềm',
        ]);
        $monHoc = MonHoc::create([
            'ma_khoa' => $khoa->ma_khoa,
            'ma_hoc_phan' => 'COS101',
            'ten_mon_hoc' => 'Lập trình Web Cơ bản',
            'so_tin_chi' => 3,
        ]);

        // 5. Tài liệu: Đang công khai, Chờ kiểm tra, Trong thùng rác, Tổng lượt tải
        $docActive = TaiLieu::create([
            'ma_mon_hoc' => $monHoc->ma_mon_hoc,
            'ma_nguoi_dang' => $admin->ma_nguoi_dung,
            'tieu_de' => 'Giáo trình Lập trình Web',
            'duong_dan_tep' => 'documents/sample.pdf',
            'dinh_dang_tep' => 'pdf',
            'dung_luong_kb' => 1024,
            'ma_bam_file' => hash('sha256', 'web101'),
            'trang_thai' => 'CONG_KHAI',
            'luot_tai' => 45,
        ]);

        $docReview = TaiLieu::create([
            'ma_mon_hoc' => $monHoc->ma_mon_hoc,
            'ma_nguoi_dang' => $admin->ma_nguoi_dung,
            'tieu_de' => 'Tài liệu cần kiểm duyệt',
            'duong_dan_tep' => 'documents/sample_review.pdf',
            'dinh_dang_tep' => 'pdf',
            'dung_luong_kb' => 512,
            'ma_bam_file' => hash('sha256', 'web_review'),
            'trang_thai' => 'CHO_KIEM_TRA',
            'luot_tai' => 5,
        ]);

        $docTrash = TaiLieu::create([
            'ma_mon_hoc' => $monHoc->ma_mon_hoc,
            'ma_nguoi_dang' => $admin->ma_nguoi_dung,
            'tieu_de' => 'Tài liệu đã xóa mềm',
            'duong_dan_tep' => 'documents/sample_trash.pdf',
            'dinh_dang_tep' => 'pdf',
            'dung_luong_kb' => 256,
            'ma_bam_file' => hash('sha256', 'web_trash'),
            'trang_thai' => 'CONG_KHAI',
            'luot_tai' => 10,
        ]);
        $docTrash->delete(); // Soft delete

        // 6. Báo cáo vi phạm chưa xử lý
        BaoCaoViPham::create([
            'ma_tai_lieu' => $docActive->ma_tai_lieu,
            'ma_nguoi_bao_cao' => $admin->ma_nguoi_dung,
            'ly_do' => 'SAI_CHUYEN_MUC',
            'chi_tiet_loi' => 'Tài liệu nhầm môn học',
            'trang_thai_xu_ly' => 'CHO_XU_LY',
        ]);

        // 7. Yêu cầu gỡ tài liệu chưa xử lý
        YeuCauGoBoTaiLieu::create([
            'ma_tai_lieu' => $docActive->ma_tai_lieu,
            'ma_nguoi_yeu_cau' => $admin->ma_nguoi_dung,
            'ly_do' => 'Tác giả yêu cầu cập nhật bản quyền mới',
            'trang_thai' => 'CHO_XU_LY',
        ]);

        // Thực hiện request đến trang Dashboard Quản trị
        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Dashboard/Index')
            // Backward-compatibility keys
            ->has('stats.pendingKyc')
            ->has('stats.pendingReports')
            ->has('stats.documentsToReview')
            ->has('stats.pendingRemovalRequests')
            ->has('stats.totalUsers')
            ->has('stats.totalDocuments')
            ->has('stats.storage')
            // Task 10: Người dùng & Tài khoản
            ->where('stats.users.total', 3)
            ->where('stats.users.active', 2)
            ->where('stats.users.locked', 1)
            // Task 10: Dữ liệu sinh viên raw
            ->where('stats.rawStudents.total', 2)
            ->where('stats.rawStudents.activated', 1)
            ->where('stats.rawStudents.unactivated', 1)
            ->where('stats.rawStudents.activationRate', fn ($rate) => (float) $rate === 50.0)
            // Task 10: Hồ sơ KYC
            ->where('stats.kyc.pendingAlumni', 1)
            ->where('stats.kyc.approved', 1)
            ->where('stats.kyc.rejected', 1)
            // Task 10: Hàng đợi kiểm duyệt & Thùng rác
            ->where('stats.moderation.pendingReports', 1)
            ->where('stats.moderation.documentsToReview', 1)
            ->where('stats.moderation.pendingRemovalRequests', 1)
            ->where('stats.moderation.trashedDocuments', 1)
            // Task 10: Danh mục đào tạo
            ->where('stats.catalog.faculties', 1)
            ->where('stats.catalog.majors', 1)
            ->where('stats.catalog.courses', 1)
            // Task 10: Tương tác tài liệu & Lượt tải
            ->where('stats.documents.totalDownloads', 60) // 45 + 5 + 10 = 60
            // Task 10: Cảnh báo nghiệp vụ cần xử lý ngay
            ->has('stats.alerts')
            ->where('stats.alerts', fn ($alerts) => count($alerts) > 0)
        );
    }

    // Khi hệ thống sạch không có tồn đọng, danh sách cảnh báo alerts rỗng
    public function test_admin_dashboard_has_empty_alerts_when_system_is_clean(): void
    {
        $admin = NguoiDung::factory()->create([
            'vai_tro' => VaiTroNguoiDung::QUAN_TRI_VIEN,
            'cap_quan_tri' => CapQuanTri::TOI_CAO,
            'trang_thai' => TrangThaiNguoiDung::DANG_HOAT_DONG,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Dashboard/Index')
            ->where('stats.alerts', [])
            ->where('stats.moderation.pendingReports', 0)
            ->where('stats.moderation.documentsToReview', 0)
            ->where('stats.moderation.pendingRemovalRequests', 0)
            ->where('stats.kyc.pending', 0)
        );
    }

    // Cảnh báo nghiệp vụ được phân cấp mức độ ưu tiên chính xác: critical -> warning -> info
    public function test_business_alerts_are_sorted_by_severity(): void
    {
        $admin = NguoiDung::factory()->create([
            'vai_tro' => VaiTroNguoiDung::QUAN_TRI_VIEN,
            'cap_quan_tri' => CapQuanTri::TOI_CAO,
            'trang_thai' => TrangThaiNguoiDung::DANG_HOAT_DONG,
        ]);

        $monHoc = MonHoc::factory()->create();

        // 1. Tạo 6 báo cáo vi phạm để kích hoạt mức critical (>= 5 báo cáo)
        for ($i = 0; $i < 6; $i++) {
            $doc = TaiLieu::create([
                'ma_mon_hoc' => $monHoc->ma_mon_hoc,
                'ma_nguoi_dang' => $admin->ma_nguoi_dung,
                'tieu_de' => 'Tài liệu test ' . $i,
                'duong_dan_tep' => 'documents/test_' . $i . '.pdf',
                'dinh_dang_tep' => 'pdf',
                'dung_luong_kb' => 128,
                'ma_bam_file' => hash('sha256', 'test_file_' . $i),
                'trang_thai' => 'CONG_KHAI',
            ]);
            BaoCaoViPham::create([
                'ma_tai_lieu' => $doc->ma_tai_lieu,
                'ma_nguoi_bao_cao' => $admin->ma_nguoi_dung,
                'ly_do' => 'VI_PHAM_BAN_QUYEN',
                'trang_thai_xu_ly' => 'CHO_XU_LY',
            ]);
        }

        // 2. Tạo 1 yêu cầu gỡ bỏ để kích hoạt mức warning
        $doc = TaiLieu::create([
            'ma_mon_hoc' => $monHoc->ma_mon_hoc,
            'ma_nguoi_dang' => $admin->ma_nguoi_dung,
            'tieu_de' => 'Tài liệu yêu cầu gỡ',
            'duong_dan_tep' => 'documents/removal.pdf',
            'dinh_dang_tep' => 'pdf',
            'dung_luong_kb' => 256,
            'ma_bam_file' => hash('sha256', 'removal_file'),
            'trang_thai' => 'CONG_KHAI',
        ]);
        YeuCauGoBoTaiLieu::create([
            'ma_tai_lieu' => $doc->ma_tai_lieu,
            'ma_nguoi_yeu_cau' => $admin->ma_nguoi_dung,
            'ly_do' => 'Yêu cầu gỡ file',
            'trang_thai' => 'CHO_XU_LY',
        ]);

        // 3. Tạo 1 hồ sơ KYC cựu sinh viên để kích hoạt mức info
        YeuCauXacThuc::create([
            'ma_nguoi_dung' => $admin->ma_nguoi_dung,
            'loai_xac_thuc' => LoaiXacThuc::CUU_SINH_VIEN,
            'mssv' => 'ALUMNI_TEST',
            'nam_tot_nghiep' => 2021,
            'duong_dan_minh_chung' => 'kyc/test.jpg',
            'loai_tep' => 'image/jpeg',
            'trang_thai' => TrangThaiXacThuc::CHO_DUYET,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Dashboard/Index')
            ->where('stats.alerts.0.severity', 'critical')
            ->where('stats.alerts.0.id', 'pending_reports')
            ->where('stats.alerts.1.severity', 'warning')
            ->where('stats.alerts.1.id', 'pending_removal_requests')
            ->where('stats.alerts.2.severity', 'info')
            ->where('stats.alerts.2.id', 'pending_kyc')
        );
    }
}
