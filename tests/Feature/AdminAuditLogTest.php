<?php

namespace Tests\Feature;

use App\Enums\CapQuanTri;
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
use App\Models\YeuCauXacThuc;
use App\Modules\Administration\Domain\Enums\HanhDongKiemToan;
use App\Modules\Administration\Domain\Enums\LoaiDoiTuongKiemToan;
use App\Modules\Administration\Domain\Services\AuditLogger;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected NguoiDung $superAdmin;
    protected NguoiDung $admin;
    protected NguoiDung $moderator;
    protected NguoiDung $student;
    protected Khoa $khoa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->khoa = Khoa::create([
            'ky_hieu_khoa' => 'CNTT',
            'ten_khoa'     => 'Công nghệ Thông tin',
        ]);

        $this->superAdmin = NguoiDung::factory()->create([
            'ho_ten'       => 'Super Administrator',
            'email'        => 'admin@agu.edu.vn',
            'mat_khau'     => Hash::make('Password123!'),
            'vai_tro'      => VaiTroNguoiDung::QUAN_TRI_VIEN,
            'cap_quan_tri' => CapQuanTri::TOI_CAO,
            'trang_thai'   => TrangThaiNguoiDung::DANG_HOAT_DONG,
        ]);

        $this->admin = NguoiDung::factory()->create([
            'ho_ten'       => 'Admin Regular',
            'email'        => 'admin.reg@agu.edu.vn',
            'mat_khau'     => Hash::make('Password123!'),
            'vai_tro'      => VaiTroNguoiDung::QUAN_TRI_VIEN,
            'cap_quan_tri' => CapQuanTri::THUONG,
            'trang_thai'   => TrangThaiNguoiDung::DANG_HOAT_DONG,
        ]);

        $this->moderator = NguoiDung::factory()->create([
            'ho_ten'     => 'Moderator One',
            'email'      => 'mod@agu.edu.vn',
            'mat_khau'   => Hash::make('Password123!'),
            'vai_tro'    => VaiTroNguoiDung::KIEM_DUYET_VIEN,
            'trang_thai' => TrangThaiNguoiDung::DANG_HOAT_DONG,
        ]);

        $this->student = NguoiDung::factory()->create([
            'ho_ten'     => 'Nguyen Van Sinh Vien',
            'email'      => 'sv@student.agu.edu.vn',
            'mssv'       => 'DTH215000',
            'mat_khau'   => Hash::make('Password123!'),
            'vai_tro'    => VaiTroNguoiDung::SINH_VIEN,
            'trang_thai' => TrangThaiNguoiDung::DANG_HOAT_DONG,
            'ma_khoa'    => $this->khoa->ma_khoa,
        ]);
    }

    /**
     * Test 1: Quyền truy cập - Người dùng thông thường hoặc moderator không được xem audit log
     */
    public function test_regular_user_and_guest_cannot_access_audit_logs(): void
    {
        // Khách chưa đăng nhập
        $this->get(route('admin.audit-logs.index'))
            ->assertRedirect(route('login'));

        // Sinh viên thông thường bị 403 Forbidden
        $this->actingAs($this->student)
            ->get(route('admin.audit-logs.index'))
            ->assertForbidden();

        // Kiểm duyệt viên cũng bị 403 (chỉ Quản trị viên mới được xem audit logs)
        $this->actingAs($this->moderator)
            ->get(route('admin.audit-logs.index'))
            ->assertForbidden();

        // Quản trị viên truy cập thành công 200 OK
        $this->actingAs($this->admin)
            ->get(route('admin.audit-logs.index'))
            ->assertOk();
    }

    /**
     * Test 2: Bất biến (Immutability) - Audit log không thể bị sửa hoặc xóa
     */
    public function test_audit_logs_are_immutable_and_cannot_be_updated_or_deleted(): void
    {
        $log = app(AuditLogger::class)->log(
            actor: $this->admin,
            action: HanhDongKiemToan::KHOA_TAI_KHOAN,
            targetType: LoaiDoiTuongKiemToan::NGUOI_DUNG,
            targetId: (string) $this->student->ma_nguoi_dung,
            description: 'Tạm khóa tài khoản kiểm thử tính bất biến',
            oldData: ['trang_thai' => 'DANG_HOAT_DONG'],
            newData: ['trang_thai' => 'TAM_KHOA']
        );

        $this->assertDatabaseHas('nhat_ky_kiem_toan', ['id' => $log->id]);

        // Thử cập nhật bản ghi kiểm toán -> Phải ném ngoại lệ DomainException
        $this->expectException(DomainException::class);
        $log->update(['mo_ta' => 'Sửa đổi trái phép nội dung nhật ký']);
    }

    /**
     * Test 2b: Xóa bản ghi kiểm toán phải bị chặn hoàn toàn
     */
    public function test_audit_log_deletion_throws_exception(): void
    {
        $log = app(AuditLogger::class)->log(
            actor: $this->admin,
            action: HanhDongKiemToan::MO_KHOA_TAI_KHOAN,
            targetType: LoaiDoiTuongKiemToan::NGUOI_DUNG,
            targetId: (string) $this->student->ma_nguoi_dung,
            description: 'Mở khóa tài khoản'
        );

        $this->expectException(DomainException::class);
        $log->delete();
    }

    /**
     * Test 3: Đăng nhập và đăng xuất khu vực Admin được ghi nhật ký
     */
    public function test_admin_login_and_logout_are_logged(): void
    {
        // 1. Đăng nhập
        $this->post(route('login'), [
            'email'    => 'admin@agu.edu.vn',
            'password' => 'Password123!',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertDatabaseHas('nhat_ky_kiem_toan', [
            'hanh_dong'          => HanhDongKiemToan::DANG_NHAP_ADMIN->value,
            'ma_nguoi_thuc_hien' => $this->superAdmin->ma_nguoi_dung,
            'loai_doi_tuong'     => LoaiDoiTuongKiemToan::PHIEN_LAM_VIEC->value,
        ]);

        // 2. Đăng xuất
        $this->actingAs($this->superAdmin)
            ->post(route('logout'))
            ->assertRedirect('/');

        $this->assertDatabaseHas('nhat_ky_kiem_toan', [
            'hanh_dong'          => HanhDongKiemToan::DANG_XUAT_ADMIN->value,
            'ma_nguoi_thuc_hien' => $this->superAdmin->ma_nguoi_dung,
            'loai_doi_tuong'     => LoaiDoiTuongKiemToan::PHIEN_LAM_VIEC->value,
        ]);
    }

    /**
     * Test 4: Khóa và mở khóa tài khoản được ghi nhật ký
     */
    public function test_lock_and_unlock_account_are_logged(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.users.status', $this->student->ma_nguoi_dung), [
                'ly_do' => 'Vi phạm nội quy chia sẻ tài liệu',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('nhat_ky_kiem_toan', [
            'hanh_dong'          => HanhDongKiemToan::KHOA_TAI_KHOAN->value,
            'ma_nguoi_thuc_hien' => $this->admin->ma_nguoi_dung,
            'loai_doi_tuong'     => LoaiDoiTuongKiemToan::NGUOI_DUNG->value,
            'ma_doi_tuong'       => (string) $this->student->ma_nguoi_dung,
        ]);

        // Mở khóa lại
        $this->actingAs($this->admin)
            ->put(route('admin.users.status', $this->student->ma_nguoi_dung), [
                'ly_do' => 'Đã hết thời hạn khóa',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('nhat_ky_kiem_toan', [
            'hanh_dong'          => HanhDongKiemToan::MO_KHOA_TAI_KHOAN->value,
            'ma_nguoi_thuc_hien' => $this->admin->ma_nguoi_dung,
            'loai_doi_tuong'     => LoaiDoiTuongKiemToan::NGUOI_DUNG->value,
            'ma_doi_tuong'       => (string) $this->student->ma_nguoi_dung,
        ]);
    }

    /**
     * Test 5: Thay đổi vai trò người dùng được ghi nhật ký
     */
    public function test_update_user_permissions_is_logged(): void
    {
        $this->actingAs($this->superAdmin)
            ->put(route('admin.users.permissions', $this->student->ma_nguoi_dung), [
                'vai_tro' => VaiTroNguoiDung::KIEM_DUYET_VIEN->value,
                'ly_do'   => 'Bổ nhiệm làm Kiểm duyệt viên sinh viên',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('nhat_ky_kiem_toan', [
            'hanh_dong'          => HanhDongKiemToan::THAY_DOI_VAI_TRO->value,
            'ma_nguoi_thuc_hien' => $this->superAdmin->ma_nguoi_dung,
            'loai_doi_tuong'     => LoaiDoiTuongKiemToan::NGUOI_DUNG->value,
            'ma_doi_tuong'       => (string) $this->student->ma_nguoi_dung,
        ]);
    }

    /**
     * Test 6: Duyệt và từ chối KYC cựu sinh viên được ghi nhật ký
     */
    public function test_approve_and_reject_kyc_are_logged(): void
    {
        $alumniUser = NguoiDung::factory()->create([
            'ho_ten'     => 'Cuu Sinh Vien Test',
            'email'      => 'alumni@gmail.com',
            'mat_khau'   => Hash::make('Password123!'),
            'vai_tro'    => VaiTroNguoiDung::CUU_SINH_VIEN,
            'trang_thai' => TrangThaiNguoiDung::CHUA_XAC_THUC,
        ]);

        $kyc = YeuCauXacThuc::create([
            'ma_nguoi_dung'         => $alumniUser->ma_nguoi_dung,
            'loai_xac_thuc'         => 'CUU_SINH_VIEN',
            'duong_dan_minh_chung'  => 'kyc/test_evidence.jpg',
            'trang_thai'            => TrangThaiXacThuc::CHO_DUYET,
        ]);

        // Duyệt KYC
        $this->actingAs($this->admin)
            ->put(route('admin.kyc.approve', $kyc->ma_yeu_cau_xac_thuc))
            ->assertRedirect();

        $this->assertDatabaseHas('nhat_ky_kiem_toan', [
            'hanh_dong'          => HanhDongKiemToan::DUYET_KYC->value,
            'ma_nguoi_thuc_hien' => $this->admin->ma_nguoi_dung,
            'loai_doi_tuong'     => LoaiDoiTuongKiemToan::YEU_CAU_XAC_THUC->value,
            'ma_doi_tuong'       => (string) $kyc->ma_yeu_cau_xac_thuc,
        ]);

        // Tạo hồ sơ thứ 2 để từ chối
        $alumniUser2 = NguoiDung::factory()->create([
            'ho_ten'     => 'Cuu Sinh Vien 2',
            'email'      => 'alumni2@gmail.com',
            'mat_khau'   => Hash::make('Password123!'),
            'vai_tro'    => VaiTroNguoiDung::CUU_SINH_VIEN,
            'trang_thai' => TrangThaiNguoiDung::CHUA_XAC_THUC,
        ]);

        $kyc2 = YeuCauXacThuc::create([
            'ma_nguoi_dung'        => $alumniUser2->ma_nguoi_dung,
            'loai_xac_thuc'        => 'CUU_SINH_VIEN',
            'duong_dan_minh_chung' => 'kyc/test_evidence2.jpg',
            'trang_thai'           => TrangThaiXacThuc::CHO_DUYET,
        ]);

        $this->actingAs($this->admin)
            ->put(route('admin.kyc.reject', $kyc2->ma_yeu_cau_xac_thuc), [
                'ly_do_tu_choi' => 'Ảnh minh chứng không rõ họ tên và bằng cấp',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('nhat_ky_kiem_toan', [
            'hanh_dong'          => HanhDongKiemToan::TU_CHOI_KYC->value,
            'ma_nguoi_thuc_hien' => $this->admin->ma_nguoi_dung,
            'loai_doi_tuong'     => LoaiDoiTuongKiemToan::YEU_CAU_XAC_THUC->value,
            'ma_doi_tuong'       => (string) $kyc2->ma_yeu_cau_xac_thuc,
        ]);
    }

    /**
     * Test 7: Thay đổi cấu hình hệ thống được ghi nhật ký
     */
    public function test_system_settings_update_is_logged(): void
    {
        $this->actingAs($this->superAdmin)
            ->put(route('admin.settings.update', 'documents.trash_retention_days'), [
                'gia_tri' => '45',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('nhat_ky_kiem_toan', [
            'hanh_dong'          => HanhDongKiemToan::THAY_DOI_CAU_HINH->value,
            'ma_nguoi_thuc_hien' => $this->superAdmin->ma_nguoi_dung,
            'loai_doi_tuong'     => LoaiDoiTuongKiemToan::CAU_HINH_HE_THONG->value,
            'ma_doi_tuong'       => 'documents.trash_retention_days',
        ]);

        $this->assertDatabaseHas('cau_hinh_he_thong', [
            'khoa'    => 'documents.trash_retention_days',
            'gia_tri' => '45',
        ]);
    }

    /**
     * Test 8: Nạp, cập nhật và xóa danh_sach_sinh_vien_raw được ghi nhật ký
     */
    public function test_raw_students_crud_operations_are_logged(): void
    {
        // 1. Nạp danh sách
        $this->actingAs($this->admin)
            ->post(route('admin.raw-students.import'), [
                'records' => [
                    [
                        'mssv'     => 'DTH215999',
                        'ho_ten'   => 'Sinh Vien Test Roster',
                        'email_sv' => 'dth215999@student.agu.edu.vn',
                        'ma_khoa'  => 'CNTT',
                    ],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('nhat_ky_kiem_toan', [
            'hanh_dong'          => HanhDongKiemToan::NAP_SINH_VIEN_RAW->value,
            'ma_nguoi_thuc_hien' => $this->admin->ma_nguoi_dung,
            'loai_doi_tuong'     => LoaiDoiTuongKiemToan::SINH_VIEN_RAW->value,
        ]);

        $rawStudent = DanhSachSinhVienRaw::timTheoMssv('DTH215999');
        $this->assertNotNull($rawStudent);

        // 2. Cập nhật sinh viên raw
        $this->actingAs($this->admin)
            ->put(route('admin.raw-students.update', $rawStudent->ma_ban_ghi), [
                'ho_ten'   => 'Sinh Vien Test Roster Cap Nhat',
                'email_sv' => 'dth215999_updated@student.agu.edu.vn',
                'ma_khoa'  => 'SPTOAN',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('nhat_ky_kiem_toan', [
            'hanh_dong'          => HanhDongKiemToan::CAP_NHAT_SINH_VIEN_RAW->value,
            'ma_nguoi_thuc_hien' => $this->admin->ma_nguoi_dung,
            'loai_doi_tuong'     => LoaiDoiTuongKiemToan::SINH_VIEN_RAW->value,
            'ma_doi_tuong'       => 'DTH215999',
        ]);

        // 3. Xóa sinh viên raw
        $this->actingAs($this->admin)
            ->delete(route('admin.raw-students.destroy', $rawStudent->ma_ban_ghi))
            ->assertRedirect();

        $this->assertDatabaseHas('nhat_ky_kiem_toan', [
            'hanh_dong'          => HanhDongKiemToan::XOA_SINH_VIEN_RAW->value,
            'ma_nguoi_thuc_hien' => $this->admin->ma_nguoi_dung,
            'loai_doi_tuong'     => LoaiDoiTuongKiemToan::SINH_VIEN_RAW->value,
            'ma_doi_tuong'       => 'DTH215999',
        ]);
    }

    /**
     * Test 9: Thay đổi Khoa, Chuyên ngành và Môn học được ghi nhật ký
     */
    public function test_academic_catalog_changes_are_logged(): void
    {
        // 1. Thêm Khoa
        $this->actingAs($this->admin)
            ->post(route('admin.catalog.faculties.store'), [
                'ky_hieu_khoa' => 'NN',
                'ten_khoa'     => 'Khoa Nông Nghiệp',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('nhat_ky_kiem_toan', [
            'hanh_dong'          => HanhDongKiemToan::THAY_DOI_KHOA->value,
            'ma_nguoi_thuc_hien' => $this->admin->ma_nguoi_dung,
            'loai_doi_tuong'     => LoaiDoiTuongKiemToan::KHOA->value,
        ]);

        // 2. Thêm Chuyên ngành
        $this->actingAs($this->admin)
            ->post(route('admin.catalog.majors.store'), [
                'ky_hieu_nganh'    => 'DPM',
                'ten_chuyen_nganh' => 'Kỹ thuật Phần mềm',
                'ma_khoa'          => $this->khoa->ma_khoa,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('nhat_ky_kiem_toan', [
            'hanh_dong'          => HanhDongKiemToan::THAY_DOI_CHUYEN_NGANH->value,
            'ma_nguoi_thuc_hien' => $this->admin->ma_nguoi_dung,
            'loai_doi_tuong'     => LoaiDoiTuongKiemToan::CHUYEN_NGANH->value,
        ]);

        $major = ChuyenNganh::first();

        // 3. Thêm Môn học
        $this->actingAs($this->admin)
            ->post(route('admin.catalog.courses.store'), [
                'ten_mon_hoc'     => 'Lập trình Web Tiên tiến',
                'ma_hoc_phan'     => 'COS301',
                'so_tin_chi'      => 3,
                'ma_khoa'         => $this->khoa->ma_khoa,
                'ma_chuyen_nganh' => [$major->ma_chuyen_nganh],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('nhat_ky_kiem_toan', [
            'hanh_dong'          => HanhDongKiemToan::THAY_DOI_MON_HOC->value,
            'ma_nguoi_thuc_hien' => $this->admin->ma_nguoi_dung,
            'loai_doi_tuong'     => LoaiDoiTuongKiemToan::MON_HOC->value,
        ]);
    }

    /**
     * Test 10: Xử lý báo cáo vi phạm và kiểm duyệt ẩn/xóa tài liệu được ghi nhật ký
     */
    public function test_violation_report_resolution_and_document_moderation_are_logged(): void
    {
        $monHoc = MonHoc::create([
            'ma_khoa'     => $this->khoa->ma_khoa,
            'ma_hoc_phan' => 'CSC101',
            'ten_mon_hoc' => 'Nhập môn Lập trình',
            'so_tin_chi'  => 3,
        ]);

        $taiLieu = TaiLieu::create([
            'ma_mon_hoc'    => $monHoc->ma_mon_hoc,
            'ma_nguoi_dang' => $this->student->ma_nguoi_dung,
            'tieu_de'       => 'Tài liệu chứa nội dung nhạy cảm',
            'duong_dan_tep' => 'documents/test_report.pdf',
            'dinh_dang_tep' => 'pdf',
            'dung_luong_kb' => 1024,
            'ma_bam_file'   => hash('sha256', 'test_doc_content'),
            'trang_thai'    => 'CHO_KIEM_TRA',
            'luot_tai'      => 0,
        ]);

        $report = BaoCaoViPham::create([
            'ma_tai_lieu'       => $taiLieu->ma_tai_lieu,
            'ma_nguoi_bao_cao'  => $this->student->ma_nguoi_dung,
            'ly_do'             => 'VI_PHAM_BAN_QUYEN',
            'chi_tiet_loi'      => 'Tài liệu sao chép trái phép giáo trình',
            'trang_thai_xu_ly'  => 'CHO_XU_LY',
        ]);

        // Moderator hoặc Admin xử lý báo cáo với hành vi HIDE
        $this->actingAs($this->admin)
            ->put(route('admin.reports.resolve', ['id' => $report->getKey()]), [
                'action'            => 'HIDE',
                'resolution_reason' => 'Tạm ẩn để xác minh bản quyền tác giả',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('nhat_ky_kiem_toan', [
            'hanh_dong'          => HanhDongKiemToan::XU_LY_BAO_CAO->value,
            'ma_nguoi_thuc_hien' => $this->admin->ma_nguoi_dung,
            'loai_doi_tuong'     => LoaiDoiTuongKiemToan::BAO_CAO_VI_PHAM->value,
            'ma_doi_tuong'       => (string) $report->getKey(),
        ]);

        $this->assertDatabaseHas('nhat_ky_kiem_toan', [
            'hanh_dong'          => HanhDongKiemToan::AN_TAI_LIEU->value,
            'ma_nguoi_thuc_hien' => $this->admin->ma_nguoi_dung,
            'loai_doi_tuong'     => LoaiDoiTuongKiemToan::TAI_LIEU->value,
            'ma_doi_tuong'       => (string) $taiLieu->ma_tai_lieu,
        ]);
    }

    /**
     * Test 11: Chi tiết audit log JSON API trả về thông tin đầy đủ và diff snapshot
     */
    public function test_audit_log_show_api_returns_detailed_diff_and_actor(): void
    {
        $log = app(AuditLogger::class)->log(
            actor: $this->superAdmin,
            action: HanhDongKiemToan::THAY_DOI_CAU_HINH,
            targetType: LoaiDoiTuongKiemToan::CAU_HINH_HE_THONG,
            targetId: 'documents.trash_retention_days',
            description: 'Thay đổi thời gian lưu trữ thùng rác từ 60 về 45 ngày.',
            oldData: ['gia_tri' => '60'],
            newData: ['gia_tri' => '45']
        );

        $response = $this->actingAs($this->superAdmin)
            ->getJson(route('admin.audit-logs.show', $log->id))
            ->assertOk();

        $response->assertJsonPath('id', $log->id);
        $response->assertJsonPath('hanh_dong.value', HanhDongKiemToan::THAY_DOI_CAU_HINH->value);
        $response->assertJsonPath('loai_doi_tuong.value', LoaiDoiTuongKiemToan::CAU_HINH_HE_THONG->value);
        $response->assertJsonPath('du_lieu_cu.gia_tri', '60');
        $response->assertJsonPath('du_lieu_moi.gia_tri', '45');
        $response->assertJsonPath('nguoi_thuc_hien.ma_nguoi_dung', $this->superAdmin->ma_nguoi_dung);
    }
}
