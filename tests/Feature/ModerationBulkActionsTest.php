<?php

namespace Tests\Feature;

use App\Enums\LoaiXacThuc;
use App\Enums\TrangThaiDanhGiaMonHoc;
use App\Enums\TrangThaiNguoiDung;
use App\Enums\TrangThaiXacThuc;
use App\Enums\VaiTroNguoiDung;
use App\Models\BaoCaoDanhGia;
use App\Models\BaoCaoViPham;
use App\Models\DanhGiaMonHoc;
use App\Models\Khoa;
use App\Models\MonHoc;
use App\Models\NguoiDung;
use App\Models\NhatKyKiemToan;
use App\Models\TaiLieu;
use App\Models\YeuCauXacThuc;
use App\Modules\Administration\Domain\Enums\HanhDongKiemToan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

// Kiểm thử các tính năng thao tác hàng loạt (Bulk Actions) của Kiểm duyệt viên và Quản trị viên
class ModerationBulkActionsTest extends TestCase
{
    use RefreshDatabase;

    protected NguoiDung $moderator;
    protected NguoiDung $admin;
    protected NguoiDung $student;
    protected Khoa $khoa;
    protected MonHoc $monHoc;

    protected function setUp(): void
    {
        parent::setUp();

        $this->khoa = Khoa::create([
            'ten_khoa'     => 'Khoa Công nghệ Thông tin',
            'ky_hieu_khoa' => 'CNTT',
            'trang_thai'   => true,
        ]);

        $this->monHoc = MonHoc::create([
            'ma_mon_hoc'  => 'IT101',
            'ma_hoc_phan' => 'HP_IT101',
            'ten_mon_hoc' => 'Lập trình nâng cao',
            'so_tin_chi'  => 3,
            'ma_khoa'     => $this->khoa->ma_khoa,
            'trang_thai'  => true,
        ]);

        $this->moderator = NguoiDung::factory()->create([
            'ma_khoa'    => $this->khoa->ma_khoa,
            'vai_tro'    => VaiTroNguoiDung::KIEM_DUYET_VIEN,
            'ho_ten'     => 'Kiểm Duyệt Viên AGU',
            'email'      => 'moderator@agu.edu.vn',
            'mssv'       => 'MOD001',
            'trang_thai' => TrangThaiNguoiDung::DANG_HOAT_DONG,
        ]);

        $this->admin = NguoiDung::factory()->create([
            'ma_khoa'    => $this->khoa->ma_khoa,
            'vai_tro'    => VaiTroNguoiDung::QUAN_TRI_VIEN,
            'ho_ten'     => 'Quản Trị Viên AGU',
            'email'      => 'admin@agu.edu.vn',
            'mssv'       => 'ADM001',
            'trang_thai' => TrangThaiNguoiDung::DANG_HOAT_DONG,
        ]);

        $this->student = NguoiDung::factory()->create([
            'ma_khoa'    => $this->khoa->ma_khoa,
            'vai_tro'    => VaiTroNguoiDung::SINH_VIEN,
            'ho_ten'     => 'Sinh Viên AGU',
            'email'      => 'student@agu.edu.vn',
            'mssv'       => 'DTH210001',
            'trang_thai' => TrangThaiNguoiDung::DANG_HOAT_DONG,
        ]);
    }

    // 1. Moderator có thể duyệt hàng loạt hồ sơ KYC
    public function test_moderator_can_bulk_approve_kyc_applications(): void
    {
        Notification::fake();
        Storage::fake('local');

        $user1 = NguoiDung::factory()->alumni()->create([
            'trang_thai' => TrangThaiNguoiDung::CHUA_XAC_THUC,
        ]);
        $user2 = NguoiDung::factory()->alumni()->create([
            'trang_thai' => TrangThaiNguoiDung::CHUA_XAC_THUC,
        ]);

        $kyc1 = YeuCauXacThuc::create([
            'ma_nguoi_dung'        => $user1->ma_nguoi_dung,
            'loai_xac_thuc'        => LoaiXacThuc::CUU_SINH_VIEN,
            'trang_thai'           => TrangThaiXacThuc::CHO_DUYET,
            'mssv'                 => $user1->mssv,
            'nam_tot_nghiep'       => 2022,
            'duong_dan_minh_chung' => 'private/kyc/proof1.jpg',
        ]);
        $kyc2 = YeuCauXacThuc::create([
            'ma_nguoi_dung'        => $user2->ma_nguoi_dung,
            'loai_xac_thuc'        => LoaiXacThuc::CUU_SINH_VIEN,
            'trang_thai'           => TrangThaiXacThuc::CHO_DUYET,
            'mssv'                 => $user2->mssv,
            'nam_tot_nghiep'       => 2021,
            'duong_dan_minh_chung' => 'private/kyc/proof2.jpg',
        ]);

        Storage::disk('local')->put('private/kyc/proof1.jpg', 'img1');
        Storage::disk('local')->put('private/kyc/proof2.jpg', 'img2');

        $response = $this->actingAs($this->moderator)
            ->post(route('admin.kyc.bulk-approve'), [
                'ids' => [$kyc1->ma_yeu_cau_xac_thuc, $kyc2->ma_yeu_cau_xac_thuc],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('yeu_cau_xac_thuc', [
            'ma_yeu_cau_xac_thuc' => $kyc1->ma_yeu_cau_xac_thuc,
            'trang_thai'          => TrangThaiXacThuc::DA_DUYET->value,
        ]);
        $this->assertDatabaseHas('yeu_cau_xac_thuc', [
            'ma_yeu_cau_xac_thuc' => $kyc2->ma_yeu_cau_xac_thuc,
            'trang_thai'          => TrangThaiXacThuc::DA_DUYET->value,
        ]);

        $this->assertEquals(TrangThaiNguoiDung::DANG_HOAT_DONG, $user1->fresh()->trang_thai);
        $this->assertEquals(TrangThaiNguoiDung::DANG_HOAT_DONG, $user2->fresh()->trang_thai);

        // Kiểm tra ví điểm được tạo cho cựu sinh viên
        $this->assertDatabaseHas('vi_diem', ['ma_nguoi_dung' => $user1->ma_nguoi_dung]);
        $this->assertDatabaseHas('vi_diem', ['ma_nguoi_dung' => $user2->ma_nguoi_dung]);

        // Kiểm tra audit log ghi nhận
        $this->assertDatabaseHas('nhat_ky_kiem_toan', [
            'ma_nguoi_thuc_hien' => $this->moderator->ma_nguoi_dung,
            'hanh_dong'          => HanhDongKiemToan::DUYET_KYC->value,
            'ma_doi_tuong'       => (string) $kyc1->ma_yeu_cau_xac_thuc,
        ]);
    }

    // 2. Moderator có thể bác bỏ hàng loạt báo cáo vi phạm
    public function test_moderator_can_bulk_dismiss_reports(): void
    {
        Notification::fake();

        // Tạo đánh giá môn học bị báo cáo
        $review = DanhGiaMonHoc::create([
            'ma_mon_hoc'      => $this->monHoc->ma_mon_hoc,
            'ma_nguoi_dung'   => $this->student->ma_nguoi_dung,
            'hoc_ky'          => '2026-1',
            'diem_do_kho'     => 3,
            'diem_thuc_tien'  => 4,
            'diem_khoi_luong' => 3,
            'noi_dung'        => 'Môn học tốt, giảng viên nhiệt tình.',
            'an_danh'         => false,
            'trang_thai'      => TrangThaiDanhGiaMonHoc::DA_DUYET->value,
        ]);

        $reviewReport = BaoCaoDanhGia::create([
            'ma_danh_gia'      => $review->ma_danh_gia,
            'ma_nguoi_bao_cao' => $this->student->ma_nguoi_dung,
            'ly_do'            => 'SPAM',
            'chi_tiet'         => 'Nội dung ngắn.',
            'trang_thai'       => 'CHO_XU_LY',
        ]);

        // Tạo tài liệu bị báo cáo
        $doc = TaiLieu::factory()->create([
            'ma_mon_hoc'    => $this->monHoc->ma_mon_hoc,
            'ma_nguoi_dang' => $this->student->ma_nguoi_dung,
            'tieu_de'       => 'Tài liệu ôn thi cuối kỳ',
        ]);

        $docReport = BaoCaoViPham::create([
            'ma_tai_lieu'      => $doc->ma_tai_lieu,
            'ma_nguoi_bao_cao' => $this->student->ma_nguoi_dung,
            'ly_do'            => 'TRUNG_LAP',
            'chi_tiet_loi'     => 'Trùng lặp với file trước.',
            'trang_thai_xu_ly' => 'CHO_XU_LY',
        ]);

        $response = $this->actingAs($this->moderator)
            ->post(route('admin.reports.bulk-dismiss'), [
                'reports' => [
                    ['id' => $reviewReport->ma_bao_cao, 'type' => 'MON_HOC'],
                    ['id' => $docReport->ma_bao_cao, 'type' => 'TAI_LIEU'],
                ],
                'resolution_reason' => 'Bác bỏ hàng loạt do không có cơ sở vi phạm.',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals('BAC_BO', $reviewReport->fresh()->trang_thai);
        $this->assertEquals('BAC_BO', $docReport->fresh()->trang_thai_xu_ly);
    }

    // 3. Moderator có thể lưu trữ hàng loạt báo cáo đã xử lý/bác bỏ
    public function test_moderator_can_bulk_archive_reports(): void
    {
        $reviewReport = BaoCaoDanhGia::create([
            'ma_danh_gia'      => null,
            'ma_nguoi_bao_cao' => $this->student->ma_nguoi_dung,
            'loai_doi_tuong'   => 'GIANG_VIEN',
            'ten_giang_vien'   => 'ThS. Nguyễn Văn A',
            'ly_do'            => 'BOI_NHO',
            'chi_tiet'         => 'Nội dung bôi nhọ.',
            'trang_thai'       => 'BAC_BO',
        ]);

        $doc = TaiLieu::factory()->create([
            'ma_mon_hoc'    => $this->monHoc->ma_mon_hoc,
            'ma_nguoi_dang' => $this->student->ma_nguoi_dung,
        ]);

        $docReport = BaoCaoViPham::create([
            'ma_tai_lieu'      => $doc->ma_tai_lieu,
            'ma_nguoi_bao_cao' => $this->student->ma_nguoi_dung,
            'ly_do'            => 'SPAM',
            'chi_tiet_loi'     => 'Spam',
            'trang_thai_xu_ly' => 'DA_XU_LY',
        ]);

        $response = $this->actingAs($this->moderator)
            ->post(route('admin.reports.bulk-archive'), [
                'reports' => [
                    ['id' => $reviewReport->ma_bao_cao, 'type' => 'GIANG_VIEN'],
                    ['id' => $docReport->ma_bao_cao, 'type' => 'TAI_LIEU'],
                ],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Kiểm tra cả 2 báo cáo đã được xóa mềm (soft deleted)
        $this->assertSoftDeleted('bao_cao_danh_gia', ['ma_bao_cao' => $reviewReport->ma_bao_cao]);
        $this->assertSoftDeleted('bao_cao_vi_pham', ['ma_bao_cao' => $docReport->ma_bao_cao]);
    }

    // 4. Moderator có thể duyệt hàng loạt tài liệu mới tải lên (CHO_DUYET -> DA_DUYET)
    public function test_moderator_can_bulk_approve_new_documents(): void
    {
        Notification::fake();

        $doc1 = TaiLieu::factory()->create([
            'ma_mon_hoc'    => $this->monHoc->ma_mon_hoc,
            'ma_nguoi_dang' => $this->student->ma_nguoi_dung,
            'tieu_de'       => 'Đề kiểm tra trắc nghiệm 1',
            'trang_thai'    => 'CHO_DUYET',
        ]);

        $doc2 = TaiLieu::factory()->create([
            'ma_mon_hoc'    => $this->monHoc->ma_mon_hoc,
            'ma_nguoi_dang' => $this->student->ma_nguoi_dung,
            'tieu_de'       => 'Đề kiểm tra trắc nghiệm 2',
            'trang_thai'    => 'CHO_DUYET',
        ]);

        $response = $this->actingAs($this->moderator)
            ->post(route('admin.documents.bulk-approve'), [
                'ids' => [$doc1->ma_tai_lieu, $doc2->ma_tai_lieu],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals('DA_DUYET', $doc1->fresh()->trang_thai);
        $this->assertEquals('DA_DUYET', $doc2->fresh()->trang_thai);

        // Kiểm tra audit log được ghi nhận
        $this->assertDatabaseHas('nhat_ky_kiem_toan', [
            'ma_nguoi_thuc_hien' => $this->moderator->ma_nguoi_dung,
            'hanh_dong'          => HanhDongKiemToan::DUYET_TAI_LIEU->value,
            'ma_doi_tuong'       => (string) $doc1->ma_tai_lieu,
        ]);
        $this->assertDatabaseHas('nhat_ky_kiem_toan', [
            'ma_nguoi_thuc_hien' => $this->moderator->ma_nguoi_dung,
            'hanh_dong'          => HanhDongKiemToan::DUYET_TAI_LIEU->value,
            'ma_doi_tuong'       => (string) $doc2->ma_tai_lieu,
        ]);
    }

    // 5. Sinh viên thông thường không được phép thực hiện thao tác hàng loạt của Moderator
    public function test_regular_student_cannot_perform_bulk_moderation_actions(): void
    {
        // Thử duyệt KYC hàng loạt
        $this->actingAs($this->student)
            ->post(route('admin.kyc.bulk-approve'), ['ids' => [1, 2]])
            ->assertForbidden();

        // Thử bác bỏ báo cáo hàng loạt
        $this->actingAs($this->student)
            ->post(route('admin.reports.bulk-dismiss'), [
                'reports' => [['id' => 1, 'type' => 'TAI_LIEU']],
            ])
            ->assertForbidden();

        // Thử lưu trữ báo cáo hàng loạt
        $this->actingAs($this->student)
            ->post(route('admin.reports.bulk-archive'), [
                'reports' => [['id' => 1, 'type' => 'TAI_LIEU']],
            ])
            ->assertForbidden();

        // Thử duyệt tài liệu hàng loạt
        $this->actingAs($this->student)
            ->post(route('admin.documents.bulk-approve'), ['ids' => [1, 2]])
            ->assertForbidden();
    }
}
