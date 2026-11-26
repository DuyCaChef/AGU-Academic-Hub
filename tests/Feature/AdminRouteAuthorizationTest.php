<?php

namespace Tests\Feature;

use App\Enums\CapQuanTri;
use App\Enums\LoaiXacThuc;
use App\Enums\TrangThaiNguoiDung;
use App\Enums\TrangThaiXacThuc;
use App\Enums\VaiTroNguoiDung;
use App\Models\BaoCaoViPham;
use App\Models\DanhSachSinhVienRaw;
use App\Models\Khoa;
use App\Models\MonHoc;
use App\Models\NguoiDung;
use App\Models\TaiLieu;
use App\Models\YeuCauXacThuc;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

// Kiểm thử toàn diện ma trận phân quyền, bảo vệ route Admin và nghiệp vụ liên quan (Task 13)
class AdminRouteAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected NguoiDung $admin;
    protected NguoiDung $moderator;
    protected NguoiDung $student;
    protected NguoiDung $alumni;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = NguoiDung::factory()->create([
            'vai_tro'      => VaiTroNguoiDung::QUAN_TRI_VIEN,
            'cap_quan_tri' => CapQuanTri::TOI_CAO,
            'trang_thai'   => TrangThaiNguoiDung::DANG_HOAT_DONG,
        ]);

        $this->moderator = NguoiDung::factory()->create([
            'vai_tro'    => VaiTroNguoiDung::KIEM_DUYET_VIEN,
            'trang_thai' => TrangThaiNguoiDung::DANG_HOAT_DONG,
        ]);

        $this->student = NguoiDung::factory()->create([
            'vai_tro'    => VaiTroNguoiDung::SINH_VIEN,
            'trang_thai' => TrangThaiNguoiDung::DANG_HOAT_DONG,
        ]);

        $this->alumni = NguoiDung::factory()->create([
            'vai_tro'    => VaiTroNguoiDung::CUU_SINH_VIEN,
            'trang_thai' => TrangThaiNguoiDung::DANG_HOAT_DONG,
        ]);
    }

    // 1. Phân quyền Route Duyệt KYC: chỉ dành cho Admin và Moderator, chặn Student và Alumni
    public function test_kyc_routes_accessible_by_admin_and_moderator_only(): void
    {
        // Khách chưa đăng nhập bị chuyển hướng về login
        $this->get(route('admin.kyc.index'))->assertRedirect(route('login'));

        // Sinh viên và cựu sinh viên bị chặn 403 Forbidden
        $this->actingAs($this->student)->get(route('admin.kyc.index'))->assertForbidden();
        $this->actingAs($this->alumni)->get(route('admin.kyc.index'))->assertForbidden();

        // Kiểm duyệt viên và Quản trị viên truy cập thành công
        $this->actingAs($this->moderator)->get(route('admin.kyc.index'))->assertOk();
        $this->actingAs($this->admin)->get(route('admin.kyc.index'))->assertOk();
    }

    // 2. Route Quản lý người dùng: chỉ dành riêng cho Admin, chặn Moderator và User
    public function test_user_management_routes_accessible_by_admin_only(): void
    {
        $this->actingAs($this->student)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($this->moderator)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($this->admin)->get(route('admin.users.index'))->assertOk();

        // Thao tác đổi trạng thái và phân quyền
        $this->actingAs($this->moderator)->put(route('admin.users.status', $this->student->ma_nguoi_dung))->assertForbidden();
        $this->actingAs($this->moderator)->put(route('admin.users.permissions', $this->student->ma_nguoi_dung))->assertForbidden();
    }

    // 3. Route Nạp dữ liệu sinh viên raw: chỉ dành riêng cho Admin
    public function test_raw_student_roster_routes_accessible_by_admin_only(): void
    {
        $this->actingAs($this->student)->get(route('admin.raw-students.index'))->assertForbidden();
        $this->actingAs($this->moderator)->get(route('admin.raw-students.index'))->assertForbidden();
        $this->actingAs($this->admin)->get(route('admin.raw-students.index'))->assertOk();

        $this->actingAs($this->moderator)->post(route('admin.raw-students.import'), [])->assertForbidden();
    }

    // 4. Route Xử lý báo cáo vi phạm: dành cho cả Admin và Moderator, chặn Student
    public function test_violation_report_routes_accessible_by_admin_and_moderator(): void
    {
        $this->actingAs($this->student)->get(route('admin.reports.index'))->assertForbidden();
        $this->actingAs($this->moderator)->get(route('admin.reports.index'))->assertRedirect(route('admin.review-reports.index', ['type' => 'TAI_LIEU']));
        $this->actingAs($this->admin)->get(route('admin.reports.index'))->assertRedirect(route('admin.review-reports.index', ['type' => 'TAI_LIEU']));
    }

    // 5. Route Khôi phục tài liệu và Xóa vĩnh viễn: chỉ dành riêng cho Admin, Moderator bị chặn 403
    public function test_restore_and_permanent_delete_routes_accessible_by_admin_only(): void
    {
        $khoa = Khoa::create(['ky_hieu_khoa' => 'CNTT', 'ten_khoa' => 'Cong Nghe Thong Tin']);
        $monHoc = MonHoc::create([
            'ma_khoa'     => $khoa->ma_khoa,
            'ma_hoc_phan' => 'COS201',
            'ten_mon_hoc' => 'Co So Du Lieu',
            'so_tin_chi'  => 3,
        ]);

        $doc = TaiLieu::create([
            'ma_mon_hoc'    => $monHoc->ma_mon_hoc,
            'ma_nguoi_dang' => $this->student->ma_nguoi_dung,
            'tieu_de'       => 'Tai lieu bi xoa',
            'duong_dan_tep' => 'documents/test.pdf',
            'dinh_dang_tep' => 'pdf',
            'dung_luong_kb' => 100,
            'ma_bam_file'   => hash('sha256', 'test_auth'),
            'trang_thai'    => 'DA_DUYET',
        ]);
        $doc->delete(); // Xóa mềm

        // Moderator cố tình khôi phục hoặc xóa vĩnh viễn -> Bị chặn 403 Forbidden
        $this->actingAs($this->moderator)
            ->post(route('admin.documents.restore', $doc->ma_tai_lieu))
            ->assertForbidden();

        $this->actingAs($this->moderator)
            ->delete(route('admin.documents.permanent-delete', $doc->ma_tai_lieu))
            ->assertForbidden();

        // Admin khôi phục thành công
        $this->actingAs($this->admin)
            ->post(route('admin.documents.restore', $doc->ma_tai_lieu))
            ->assertRedirect();

        $this->assertNull($doc->fresh()->ngay_xoa_tam);
    }

    // 6. Route Quản trị Khoa, Chuyên ngành và Môn học: chỉ dành riêng cho Admin
    public function test_academic_catalog_management_accessible_by_admin_only(): void
    {
        $this->actingAs($this->student)->get(route('admin.catalog.index'))->assertForbidden();
        $this->actingAs($this->moderator)->get(route('admin.catalog.index'))->assertForbidden();
        $this->actingAs($this->admin)->get(route('admin.catalog.index'))->assertOk();

        $this->actingAs($this->moderator)
            ->post(route('admin.catalog.faculties.store'), [
                'ky_hieu_khoa' => 'SP',
                'ten_khoa'     => 'Khoa Su Pham',
            ])
            ->assertForbidden();
    }

    // 7. Sinh viên đang học không được phép tự đăng ký tài khoản qua form register
    public function test_current_student_cannot_self_register(): void
    {
        DanhSachSinhVienRaw::create([
            'mssv'              => 'DTH215999',
            'ho_ten'            => 'Sinh Vien Khong Duoc Dang Ky',
            'email_sv'          => 'dth215999@student.agu.edu.vn',
            'mat_khau_mac_dinh' => Hash::make('Agu@123456'),
        ]);

        // Thử đăng ký bằng email trường cấp
        $response = $this->post('/dang-ky', [
            'ho_ten'                => 'Sinh Vien Test',
            'email'                 => 'dth215999@student.agu.edu.vn',
            'mssv'                  => 'DTH215999',
            'mat_khau'              => 'MatKhau@123',
            'mat_khau_confirmation' => 'MatKhau@123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertDatabaseMissing('nguoi_dung', [
            'email' => 'dth215999@student.agu.edu.vn',
        ]);
    }

    // 8. Sinh viên đang học kích hoạt bằng đổi mật khẩu lần đầu và được cấp ví 100 điểm
    public function test_student_activation_via_first_time_password_change_initializes_wallet(): void
    {
        $raw = DanhSachSinhVienRaw::create([
            'mssv'              => 'DTH220001',
            'ho_ten'            => 'Nguyen Van Sinh Vien Mới',
            'email_sv'          => 'dth220001@student.agu.edu.vn',
            'mat_khau_mac_dinh' => Hash::make('Agu@123456'),
            'da_kich_hoat'      => false,
        ]);

        $student = NguoiDung::factory()->create([
            'mssv'       => 'DTH220001',
            'email'      => 'dth220001@student.agu.edu.vn',
            'vai_tro'    => VaiTroNguoiDung::SINH_VIEN,
            'trang_thai' => TrangThaiNguoiDung::CHUA_XAC_THUC,
        ]);

        $this->actingAs($student)
            ->post(route('password.change.update'), [
                'password'              => 'M@tKhauMoi#2026',
                'password_confirmation' => 'M@tKhauMoi#2026',
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertEquals(TrangThaiNguoiDung::DANG_HOAT_DONG, $student->fresh()->trang_thai);
        $this->assertTrue((bool) $raw->fresh()->da_kich_hoat);
        $this->assertDatabaseHas('vi_diem', [
            'ma_nguoi_dung' => $student->ma_nguoi_dung,
            'diem_kha_dung' => 100,
        ]);
    }

    // 9. Cựu sinh viên kích hoạt qua duyệt KYC và được cấp ví 100 điểm
    public function test_alumni_activation_via_kyc_approval_initializes_wallet(): void
    {
        $alumniPending = NguoiDung::factory()->alumni()->create([
            'trang_thai' => TrangThaiNguoiDung::CHUA_XAC_THUC,
        ]);

        $kyc = YeuCauXacThuc::create([
            'ma_nguoi_dung'        => $alumniPending->ma_nguoi_dung,
            'loai_xac_thuc'        => LoaiXacThuc::CUU_SINH_VIEN,
            'trang_thai'           => TrangThaiXacThuc::CHO_DUYET,
            'mssv'                 => $alumniPending->mssv,
            'nam_tot_nghiep'       => 2021,
            'duong_dan_minh_chung' => 'private/kyc/test.jpg',
        ]);

        $this->actingAs($this->admin)
            ->put(route('admin.kyc.approve', $kyc->ma_yeu_cau_xac_thuc))
            ->assertSessionHas('success');

        $this->assertEquals(TrangThaiNguoiDung::DANG_HOAT_DONG, $alumniPending->fresh()->trang_thai);
        $this->assertEquals(TrangThaiXacThuc::DA_DUYET, $kyc->fresh()->trang_thai);
        $this->assertDatabaseHas('vi_diem', [
            'ma_nguoi_dung' => $alumniPending->ma_nguoi_dung,
            'diem_kha_dung' => 100,
        ]);
    }
}

