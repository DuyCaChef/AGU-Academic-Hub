<?php

namespace Tests\Feature;

use App\Enums\LoaiXacThuc;
use App\Enums\TrangThaiNguoiDung;
use App\Enums\TrangThaiXacThuc;
use App\Enums\VaiTroNguoiDung;
use App\Models\DanhSachSinhVienRaw;
use App\Models\Khoa;
use App\Models\NguoiDung;
use App\Models\YeuCauXacThuc;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

// Kiểm thử toàn diện các luồng xác thực, đối soát dữ liệu thô và tài khoản thuộc Phase 6 Identity
class IdentityAuthComprehensiveTest extends TestCase
{
    use RefreshDatabase;

    protected Khoa $faculty;

    protected function setUp(): void
    {
        parent::setUp();
        $this->faculty = Khoa::create(['ten_khoa' => 'Khoa Cong Nghe Thong Tin', 'ky_hieu_khoa' => 'CNTT']);
    }

    // Kiểm tra đăng nhập bằng mã số sinh viên (MSSV)
    public function test_can_login_using_mssv(): void
    {
        $student = NguoiDung::factory()->create([
            'email' => 'dth210001@student.agu.edu.vn',
            'mssv' => 'DTH210001',
            'mat_khau' => 'matkhau123',
            'trang_thai' => TrangThaiNguoiDung::DANG_HOAT_DONG,
        ]);

        $response = $this->post(route('login'), [
            'email' => 'DTH210001',
            'password' => 'matkhau123',
        ]);

        $this->assertAuthenticatedAs($student);
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    // Kiểm tra tự động tạo tài khoản từ dữ liệu thô (raw) và chuyển hướng đổi mật khẩu lần đầu
    public function test_auto_provisions_student_from_raw_data_on_first_login(): void
    {
        DanhSachSinhVienRaw::create([
            'mssv' => 'DTH225566',
            'ho_ten' => 'Nguyen Van A',
            'email_sv' => 'dth225566@student.agu.edu.vn',
            'ma_khoa' => 'CNTT',
            'mat_khau_mac_dinh' => Hash::make('agu@default123'),
        ]);

        $response = $this->post(route('login'), [
            'email' => 'DTH225566',
            'password' => 'agu@default123',
        ]);

        // Đã tự động tạo bản ghi trong nguoi_dung
        $this->assertDatabaseHas('nguoi_dung', [
            'mssv' => 'DTH225566',
            'trang_thai' => TrangThaiNguoiDung::CHUA_XAC_THUC->value,
        ]);

        $createdUser = NguoiDung::where('mssv', 'DTH225566')->first();
        $this->assertNotNull($createdUser);
        $this->assertAuthenticatedAs($createdUser);

        // Chuyển hướng bắt buộc đổi mật khẩu lần đầu
        $response->assertRedirect(route('password.change'));
    }

    // Kiểm tra chặn đăng nhập đối với tài khoản đang bị tạm khóa
    public function test_blocks_login_for_suspended_account(): void
    {
        $user = NguoiDung::factory()->create([
            'email' => 'suspended@agu.edu.vn',
            'mat_khau' => 'secret123',
            'trang_thai' => TrangThaiNguoiDung::TAM_KHOA,
        ]);

        $response = $this->post(route('login'), [
            'email' => 'suspended@agu.edu.vn',
            'password' => 'secret123',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors(['email']);
    }

    // Kiểm tra chặn đăng nhập cựu sinh viên khi hồ sơ KYC đang chờ đối soát
    public function test_blocks_login_for_pending_alumni_kyc(): void
    {
        $alumni = NguoiDung::factory()->alumni()->create([
            'email' => 'alumni_pending@agu.edu.vn',
            'mat_khau' => 'secret123',
            'trang_thai' => TrangThaiNguoiDung::CHUA_XAC_THUC,
        ]);

        YeuCauXacThuc::create([
            'ma_nguoi_dung' => $alumni->ma_nguoi_dung,
            'loai_xac_thuc' => LoaiXacThuc::CUU_SINH_VIEN,
            'trang_thai' => TrangThaiXacThuc::CHO_DUYET,
            'duong_dan_minh_chung' => 'kyc/sample.jpg',
            'ten_tep_goc' => 'sample.jpg',
        ]);

        $response = $this->post(route('login'), [
            'email' => 'alumni_pending@agu.edu.vn',
            'password' => 'secret123',
        ]);

        $this->assertGuest();
        $response->assertSessionHas('kyc_status');
    }

    // Kiểm tra thông báo lý do từ chối nếu hồ sơ KYC cựu sinh viên bị từ chối
    public function test_shows_rejection_reason_for_rejected_alumni_kyc(): void
    {
        $alumni = NguoiDung::factory()->alumni()->create([
            'email' => 'alumni_rejected@agu.edu.vn',
            'mat_khau' => 'secret123',
            'trang_thai' => TrangThaiNguoiDung::CHUA_XAC_THUC,
        ]);

        YeuCauXacThuc::create([
            'ma_nguoi_dung' => $alumni->ma_nguoi_dung,
            'loai_xac_thuc' => LoaiXacThuc::CUU_SINH_VIEN,
            'trang_thai' => TrangThaiXacThuc::TU_CHOI,
            'ly_do_tu_choi' => 'Anh bang tot nghiep bi mo khong ro thong tin.',
            'duong_dan_minh_chung' => 'kyc/blurred.jpg',
            'ten_tep_goc' => 'blurred.jpg',
            'thoi_gian_duyet' => now(),
        ]);

        $response = $this->post(route('login'), [
            'email' => 'alumni_rejected@agu.edu.vn',
            'password' => 'secret123',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors(['email']);
    }

    // Kiểm tra đăng xuất thành công
    public function test_can_logout_successfully(): void
    {
        $user = NguoiDung::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}

