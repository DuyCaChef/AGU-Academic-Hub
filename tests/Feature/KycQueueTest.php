<?php

namespace Tests\Feature;

use App\Enums\LoaiXacThuc;
use App\Enums\TrangThaiNguoiDung;
use App\Enums\TrangThaiXacThuc;
use App\Models\DanhSachSinhVienRaw;
use App\Models\NguoiDung;
use App\Models\YeuCauXacThuc;
use App\Notifications\KycDecisionNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KycQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_student_cannot_submit_alumni_kyc(): void
    {
        DanhSachSinhVienRaw::create([
            'mssv' => 'DTH215001',
            'ho_ten' => 'Sinh Vien Hien Hanh',
            'email_sv' => 'dth215001@student.agu.edu.vn',
            'mat_khau_mac_dinh' => Hash::make('Agu@123456'),
        ]);

        $response = $this->post(route('alumni.register.store'), [
            'ho_ten' => 'Sinh Vien Hien Hanh',
            'email' => 'alumni@example.com',
            'mssv' => 'dTH215001',
            'nam_tot_nghiep' => 2020,
            'mat_khau' => 'password123',
            'mat_khau_confirmation' => 'password123',
            'loai_minh_chung' => 'BANG_TOT_NGHIEP',
            'anh_minh_chung' => UploadedFile::fake()->image('proof.jpg'),
        ]);

        $response->assertSessionHasErrors('mssv');
        $this->assertDatabaseCount('nguoi_dung', 0);
    }

    public function test_queue_only_contains_pending_alumni_requests(): void
    {
        $admin = NguoiDung::factory()->admin()->create();
        $alumni = NguoiDung::factory()->alumni()->create();
        $student = NguoiDung::factory()->create(['mssv' => 'DTH215001']);

        YeuCauXacThuc::create([
            'ma_nguoi_dung' => $alumni->ma_nguoi_dung,
            'loai_xac_thuc' => LoaiXacThuc::CUU_SINH_VIEN,
            'trang_thai' => TrangThaiXacThuc::CHO_DUYET,
            'mssv' => $alumni->mssv,
            'nam_tot_nghiep' => $alumni->nam_tot_nghiep,
            'duong_dan_minh_chung' => 'private/kyc/alumni.jpg',
        ]);
        YeuCauXacThuc::create([
            'ma_nguoi_dung' => $student->ma_nguoi_dung,
            'loai_xac_thuc' => LoaiXacThuc::SINH_VIEN,
            'trang_thai' => TrangThaiXacThuc::CHO_DUYET,
            'mssv' => $student->mssv,
            'duong_dan_minh_chung' => 'private/kyc/student.jpg',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.kyc.index'));

        $response->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->where('kycQueue.total', 1)
                ->where('kycQueue.data.0.ma_nguoi_dung', $alumni->ma_nguoi_dung));
    }

    public function test_approval_activates_alumni_and_notifies_them(): void
    {
        Notification::fake();
        Storage::fake('local');

        $admin = NguoiDung::factory()->admin()->create();
        $alumni = NguoiDung::factory()->alumni()->create([
            'trang_thai' => TrangThaiNguoiDung::CHUA_XAC_THUC,
        ]);
        $kyc = YeuCauXacThuc::create([
            'ma_nguoi_dung' => $alumni->ma_nguoi_dung,
            'loai_xac_thuc' => LoaiXacThuc::CUU_SINH_VIEN,
            'trang_thai' => TrangThaiXacThuc::CHO_DUYET,
            'mssv' => $alumni->mssv,
            'nam_tot_nghiep' => $alumni->nam_tot_nghiep,
            'duong_dan_minh_chung' => 'private/kyc/proof.jpg',
        ]);

        Storage::disk('local')->put('private/kyc/proof.jpg', 'proof');

        $response = $this->actingAs($admin)->put(route('admin.kyc.approve', $kyc), []);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('yeu_cau_xac_thuc', [
            'ma_yeu_cau_xac_thuc' => $kyc->ma_yeu_cau_xac_thuc,
            'trang_thai' => TrangThaiXacThuc::DA_DUYET->value,
            'nguoi_duyet' => $admin->ma_nguoi_dung,
        ]);
        $this->assertDatabaseHas('nguoi_dung', [
            'ma_nguoi_dung' => $alumni->ma_nguoi_dung,
            'trang_thai' => TrangThaiNguoiDung::DANG_HOAT_DONG->value,
        ]);
        $this->assertDatabaseHas('vi_diem', [
            'ma_nguoi_dung' => $alumni->ma_nguoi_dung,
            'diem_kha_dung' => 100,
        ]);
        Notification::assertSentTo($alumni, KycDecisionNotification::class);
    }
}
