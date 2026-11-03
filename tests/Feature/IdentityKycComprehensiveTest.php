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
use App\Modules\Identity\Infrastructure\Notifications\AlumniKycDecisionNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class IdentityKycComprehensiveTest extends TestCase
{
    use RefreshDatabase;

    // Khách có thể xem form đăng ký cựu sinh viên
    public function test_guest_can_view_alumni_registration_form(): void
    {
        Khoa::factory()->create(['ten_khoa' => 'Khoa Cong Nghe Thong Tin']);

        $response = $this->get(route('alumni.register'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Auth/AlumniRegister')
            ->has('danh_sach_khoa', 1)
        );
    }

    // Khách nộp hồ sơ KYC hợp lệ thành công
    public function test_alumni_can_submit_valid_registration(): void
    {
        Storage::fake('local');

        $khoa = Khoa::factory()->create();

        $response = $this->post(route('alumni.register.store'), [
            'ho_ten'                => 'Tran Van Cuu Sinh Vien',
            'email'                 => 'alumni.tran@gmail.com',
            'mssv'                  => 'DTH171001',
            'nam_tot_nghiep'        => 2021,
            'ma_khoa'               => $khoa->ma_khoa,
            'mat_khau'              => 'MatKhau@123',
            'mat_khau_confirmation' => 'MatKhau@123',
            'loai_minh_chung'       => 'BANG_TOT_NGHIEP',
            'anh_minh_chung'        => UploadedFile::fake()->image('bang_tot_nghiep.jpg'),
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('nguoi_dung', [
            'email'      => 'alumni.tran@gmail.com',
            'mssv'       => 'DTH171001',
            'vai_tro'    => VaiTroNguoiDung::CUU_SINH_VIEN->value,
            'trang_thai' => TrangThaiNguoiDung::CHUA_XAC_THUC->value,
        ]);

        $this->assertDatabaseHas('yeu_cau_xac_thuc', [
            'mssv'            => 'DTH171001',
            'loai_xac_thuc'   => LoaiXacThuc::CUU_SINH_VIEN->value,
            'trang_thai'      => TrangThaiXacThuc::CHO_DUYET->value,
            'loai_minh_chung' => 'BANG_TOT_NGHIEP',
        ]);
    }

    // Quản trị viên từ chối hồ sơ KYC kèm lý do
    public function test_admin_can_reject_kyc_with_reason(): void
    {
        Notification::fake();

        $admin = NguoiDung::factory()->admin()->create();
        $alumni = NguoiDung::factory()->alumni()->create([
            'trang_thai' => TrangThaiNguoiDung::CHUA_XAC_THUC,
        ]);

        $kyc = YeuCauXacThuc::create([
            'ma_nguoi_dung'        => $alumni->ma_nguoi_dung,
            'loai_xac_thuc'        => LoaiXacThuc::CUU_SINH_VIEN,
            'trang_thai'           => TrangThaiXacThuc::CHO_DUYET,
            'mssv'                 => $alumni->mssv,
            'nam_tot_nghiep'       => $alumni->nam_tot_nghiep,
            'duong_dan_minh_chung' => 'private/kyc/unclear.jpg',
        ]);

        $response = $this->actingAs($admin)->put(route('admin.kyc.reject', $kyc->ma_yeu_cau_xac_thuc), [
            'ly_do_tu_choi' => 'Ảnh chụp bằng tốt nghiệp bị mờ, không thấy rõ số hiệu.',
        ]);

        $response->assertSessionHas('warning');

        $this->assertDatabaseHas('yeu_cau_xac_thuc', [
            'ma_yeu_cau_xac_thuc' => $kyc->ma_yeu_cau_xac_thuc,
            'trang_thai'          => TrangThaiXacThuc::TU_CHOI->value,
            'ly_do_tu_choi'       => 'Ảnh chụp bằng tốt nghiệp bị mờ, không thấy rõ số hiệu.',
            'nguoi_duyet'         => $admin->ma_nguoi_dung,
        ]);

        Notification::assertSentTo($alumni, AlumniKycDecisionNotification::class);
    }

    // Người kiểm duyệt có thể xem trước ảnh minh chứng qua Signed URL
    public function test_moderator_can_view_proof_preview_via_signed_url(): void
    {
        Storage::fake('local');

        $mod = NguoiDung::factory()->moderator()->create();
        $alumni = NguoiDung::factory()->alumni()->create();

        $filePath = 'private/kyc/proof_sample.jpg';
        Storage::disk('local')->put($filePath, 'fake image content');

        $kyc = YeuCauXacThuc::create([
            'ma_nguoi_dung'        => $alumni->ma_nguoi_dung,
            'loai_xac_thuc'        => LoaiXacThuc::CUU_SINH_VIEN,
            'trang_thai'           => TrangThaiXacThuc::CHO_DUYET,
            'mssv'                 => $alumni->mssv,
            'nam_tot_nghiep'       => $alumni->nam_tot_nghiep,
            'duong_dan_minh_chung' => $filePath,
            'loai_tep'             => 'image/jpeg',
        ]);

        $signedUrl = URL::temporarySignedRoute(
            'kyc.preview',
            now()->addMinutes(30),
            ['ma_xac_thuc' => $kyc->ma_yeu_cau_xac_thuc]
        );

        $response = $this->actingAs($mod)->get($signedUrl);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/jpeg');
    }

    // Sinh viên bình thường không có quyền truy cập hàng đợi KYC
    public function test_regular_student_cannot_access_kyc_queue(): void
    {
        $student = NguoiDung::factory()->create();

        $response = $this->actingAs($student)->get(route('admin.kyc.index'));
        $response->assertForbidden();
    }

    // Sinh viên đăng nhập lần đầu thực hiện đổi mật khẩu bắt buộc
    public function test_student_first_time_password_change_flow(): void
    {
        $rawStudent = DanhSachSinhVienRaw::create([
            'mssv'              => 'DTH205111',
            'ho_ten'            => 'Le Van Sinh Vien',
            'email_sv'          => 'dth205111@student.agu.edu.vn',
            'mat_khau_mac_dinh' => Hash::make('Agu@123456'),
        ]);

        $student = NguoiDung::factory()->create([
            'mssv'       => 'DTH205111',
            'trang_thai' => TrangThaiNguoiDung::CHUA_XAC_THUC,
        ]);

        // 1. Màn hình đổi mật khẩu lần đầu
        $response = $this->actingAs($student)->get(route('password.change'));
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Auth/FirstTimeChangePassword')
            ->where('user.mssv', 'DTH205111')
        );

        // 2. Gửi mật khẩu mới
        $postResponse = $this->actingAs($student)->post(route('password.change.update'), [
            'password'              => 'NewSecurePass@2026',
            'password_confirmation' => 'NewSecurePass@2026',
        ]);

        $postResponse->assertRedirect(route('dashboard'));
        $postResponse->assertSessionHas('success');

        $this->assertDatabaseHas('nguoi_dung', [
            'ma_nguoi_dung' => $student->ma_nguoi_dung,
            'trang_thai'    => TrangThaiNguoiDung::DANG_HOAT_DONG->value,
        ]);

        $this->assertDatabaseHas('danh_sach_sinh_vien_raw', [
            'mssv'         => 'DTH205111',
            'da_kich_hoat' => true,
        ]);
    }
}

