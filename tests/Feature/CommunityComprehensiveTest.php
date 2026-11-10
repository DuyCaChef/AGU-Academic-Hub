<?php

namespace Tests\Feature;

use App\Enums\LoaiGiaoDichDiem;
use App\Enums\TrangThaiYeuCauTaiLieu;
use App\Enums\VaiTroNguoiDung;
use App\Models\BaoCaoDanhGia;
use App\Models\BinhChonDanhGia;
use App\Models\DanhGiaMonHoc;
use App\Models\DapUngYeuCauTaiLieu;
use App\Models\Khoa;
use App\Models\MonHoc;
use App\Models\NguoiDung;
use App\Models\TaiLieu;
use App\Models\YeuCauTaiLieu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Kiểm thử toàn diện cho phân hệ Reviews & Bounties sau tái cấu trúc Modular Monolith
class CommunityComprehensiveTest extends TestCase
{
    use RefreshDatabase;

    protected Khoa $faculty;
    protected MonHoc $course;
    protected NguoiDung $studentA;
    protected NguoiDung $studentB;
    protected NguoiDung $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->faculty = Khoa::create(['ten_khoa' => 'Khoa Cong Nghe', 'ky_hieu_khoa' => 'CNTT']);
        $this->studentA = NguoiDung::factory()->create([
            'email' => 'studenta@agu.edu.vn',
            'ma_khoa' => $this->faculty->ma_khoa,
            'vai_tro' => VaiTroNguoiDung::SINH_VIEN,
        ]);
        $this->studentB = NguoiDung::factory()->create([
            'email' => 'studentb@agu.edu.vn',
            'ma_khoa' => $this->faculty->ma_khoa,
            'vai_tro' => VaiTroNguoiDung::SINH_VIEN,
        ]);
        $this->admin = NguoiDung::factory()->create([
            'email' => 'admin@agu.edu.vn',
            'ma_khoa' => $this->faculty->ma_khoa,
            'vai_tro' => VaiTroNguoiDung::QUAN_TRI_VIEN,
        ]);

        $this->course = MonHoc::create([
            'ma_khoa' => $this->faculty->ma_khoa,
            'ma_hoc_phan' => 'COS202',
            'ten_mon_hoc' => 'Co so du lieu nang cao',
            'so_tin_chi' => 3,
            'mo_ta' => 'Mon hoc co so nganh',
        ]);
    }

    // Kiểm tra tác giả có thể cập nhật đánh giá của chính mình
    public function test_author_can_update_review(): void
    {
        $review = DanhGiaMonHoc::create([
            'ma_mon_hoc' => $this->course->ma_mon_hoc,
            'ma_nguoi_dung' => $this->studentA->ma_nguoi_dung,
            'hoc_ky' => '2025-1',
            'diem_do_kho' => 3,
            'diem_thuc_tien' => 4,
            'diem_khoi_luong' => 3,
            'noi_dung' => 'Mon hoc noi dung ban dau rat hay.',
            'an_danh' => false,
            'trang_thai' => 'DA_DUYET',
        ]);

        $response = $this->actingAs($this->studentA)
            ->put(route('reviews.update', $review->ma_danh_gia), [
                'diem_do_kho' => 4,
                'diem_thuc_tien' => 5,
                'diem_khoi_luong' => 4,
                'noi_dung' => 'Mon hoc noi dung cap nhat cang hay hon rat nhieu.',
                'an_danh' => true,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('danh_gia_mon_hoc', [
            'ma_danh_gia' => $review->ma_danh_gia,
            'diem_do_kho' => 4,
            'diem_thuc_tien' => 5,
            'diem_khoi_luong' => 4,
            'noi_dung' => 'Mon hoc noi dung cap nhat cang hay hon rat nhieu.',
            'an_danh' => 1,
        ]);
    }

    // Kiểm tra người khác không được quyền cập nhật đánh giá của tác giả
    public function test_unauthorized_user_cannot_update_review(): void
    {
        $review = DanhGiaMonHoc::create([
            'ma_mon_hoc' => $this->course->ma_mon_hoc,
            'ma_nguoi_dung' => $this->studentA->ma_nguoi_dung,
            'hoc_ky' => '2025-1',
            'diem_do_kho' => 3,
            'diem_thuc_tien' => 4,
            'diem_khoi_luong' => 3,
            'noi_dung' => 'Mon hoc noi dung ban dau rat hay.',
            'an_danh' => false,
            'trang_thai' => 'DA_DUYET',
        ]);

        $response = $this->actingAs($this->studentB)
            ->put(route('reviews.update', $review->ma_danh_gia), [
                'diem_do_kho' => 1,
                'diem_thuc_tien' => 1,
                'diem_khoi_luong' => 1,
                'noi_dung' => 'Thu sua bai cua nguoi khac xem sao.',
            ]);

        $response->assertStatus(403);
    }

    // Kiểm tra xóa mềm bài đánh giá bởi tác giả hoặc quản trị viên
    public function test_author_or_admin_can_delete_review(): void
    {
        $review = DanhGiaMonHoc::create([
            'ma_mon_hoc' => $this->course->ma_mon_hoc,
            'ma_nguoi_dung' => $this->studentA->ma_nguoi_dung,
            'hoc_ky' => '2025-1',
            'diem_do_kho' => 3,
            'diem_thuc_tien' => 4,
            'diem_khoi_luong' => 3,
            'noi_dung' => 'Bai danh gia chuan bi duoc xoa boi admin.',
            'an_danh' => false,
            'trang_thai' => 'DA_DUYET',
        ]);

        // Người dùng B không có quyền xóa
        $failResponse = $this->actingAs($this->studentB)
            ->delete(route('reviews.destroy', $review->ma_danh_gia));
        $failResponse->assertStatus(403);

        // Admin xóa thành công
        $successResponse = $this->actingAs($this->admin)
            ->delete(route('reviews.destroy', $review->ma_danh_gia));
        $successResponse->assertRedirect();

        $this->assertSoftDeleted('danh_gia_mon_hoc', [
            'ma_danh_gia' => $review->ma_danh_gia,
        ]);
    }

    // Kiểm tra không cho phép tự bình chọn bài đánh giá của chính mình
    public function test_cannot_vote_helpful_on_own_review(): void
    {
        $review = DanhGiaMonHoc::create([
            'ma_mon_hoc' => $this->course->ma_mon_hoc,
            'ma_nguoi_dung' => $this->studentA->ma_nguoi_dung,
            'hoc_ky' => '2025-1',
            'diem_do_kho' => 3,
            'diem_thuc_tien' => 4,
            'diem_khoi_luong' => 3,
            'noi_dung' => 'Tu vote cho chinh minh se bi tu choi.',
            'an_danh' => false,
            'trang_thai' => 'DA_DUYET',
        ]);

        $response = $this->actingAs($this->studentA)
            ->post(route('reviews.helpful', $review->ma_danh_gia));

        $response->assertRedirect();
        $response->assertSessionHas('warning', 'Bạn không thể tự bình chọn đánh giá của chính mình.');
        $this->assertDatabaseMissing('binh_chon_danh_gia', [
            'ma_danh_gia' => $review->ma_danh_gia,
            'ma_nguoi_dung' => $this->studentA->ma_nguoi_dung,
        ]);
    }

    // Kiểm tra báo cáo vi phạm bài đánh giá
    public function test_can_report_review_violation(): void
    {
        $review = DanhGiaMonHoc::create([
            'ma_mon_hoc' => $this->course->ma_mon_hoc,
            'ma_nguoi_dung' => $this->studentA->ma_nguoi_dung,
            'hoc_ky' => '2025-1',
            'diem_do_kho' => 3,
            'diem_thuc_tien' => 4,
            'diem_khoi_luong' => 3,
            'noi_dung' => 'Bai danh gia bi nghi ngo vi pham tieu chuan.',
            'an_danh' => false,
            'trang_thai' => 'DA_DUYET',
        ]);

        $response = $this->actingAs($this->studentB)
            ->post(route('reviews.report', $review->ma_danh_gia), [
                'ly_do' => 'SPAM',
                'chi_tiet' => 'Bai viet quang cao khong phu hop.',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('bao_cao_danh_gia', [
            'ma_danh_gia' => $review->ma_danh_gia,
            'ma_nguoi_bao_cao' => $this->studentB->ma_nguoi_dung,
            'ly_do' => 'SPAM',
        ]);
    }

    // Kiểm tra đóng góp tài liệu và xác nhận giải ngân Bounty Points
    public function test_fulfill_and_confirm_bounty_workflow(): void
    {
        // 1. Student A tạo yêu cầu tìm tài liệu (bounty 50)
        $yeuCau = YeuCauTaiLieu::create([
            'ma_mon_hoc' => $this->course->ma_mon_hoc,
            'ma_nguoi_tao' => $this->studentA->ma_nguoi_dung,
            'tieu_de' => 'De thi giua ky COS202',
            'phan_loai' => 'DE_THI',
            'diem_thuong' => 50,
            'trang_thai' => TrangThaiYeuCauTaiLieu::MO,
        ]);

        // Đóng băng điểm của Student A trong ví
        app(\App\Modules\Bounties\Domain\Services\ReputationEvaluator::class)
            ->dongBangDiemBounty($this->studentA, 50);

        // Tạo tài liệu mẫu của Student B
        $doc = TaiLieu::create([
            'ma_mon_hoc' => $this->course->ma_mon_hoc,
            'ma_nguoi_dang' => $this->studentB->ma_nguoi_dung,
            'tieu_de' => 'De thi giua ky nam 2024',
            'duong_dan_tep' => 'documents/dethi2024.pdf',
            'dinh_dang_tep' => 'pdf',
            'dung_luong_kb' => 120,
            'ma_bam_file' => hash('sha256', 'dethi2024'),
            'phan_loai' => 'DE_THI',
            'trang_thai' => 'DA_DUYET',
        ]);

        // Student A không thể tự nộp tài liệu cho yêu cầu của mình
        $selfFulfill = $this->actingAs($this->studentA)
            ->post(route('document-requests.fulfill', $yeuCau->ma_yeu_cau), [
                'ma_tai_lieu' => $doc->ma_tai_lieu,
                'ghi_chu_dong_gop' => 'Tu nop cho minh',
            ]);
        $selfFulfill->assertRedirect();
        $selfFulfill->assertSessionHas('error');

        // Student B nộp tài liệu thành công
        $fulfillResponse = $this->actingAs($this->studentB)
            ->post(route('document-requests.fulfill', $yeuCau->ma_yeu_cau), [
                'ma_tai_lieu' => $doc->ma_tai_lieu,
                'ghi_chu_dong_gop' => 'Gui ban de thi nhe',
            ]);
        $fulfillResponse->assertRedirect();
        $fulfillResponse->assertSessionHas('success');

        $dapUng = DapUngYeuCauTaiLieu::where('ma_yeu_cau', $yeuCau->ma_yeu_cau)->first();
        $this->assertNotNull($dapUng);
        $this->assertEquals('CHO_DUYET', $dapUng->trang_thai);

        // Student B nộp lại cùng tài liệu đó thì bị từ chối trùng lặp
        $dupFulfill = $this->actingAs($this->studentB)
            ->post(route('document-requests.fulfill', $yeuCau->ma_yeu_cau), [
                'ma_tai_lieu' => $doc->ma_tai_lieu,
            ]);
        $dupFulfill->assertRedirect();
        $dupFulfill->assertSessionHas('error');

        // Student A xác nhận tài liệu hợp lệ -> Giải ngân 50 điểm
        $confirmResponse = $this->actingAs($this->studentA)
            ->post(route('document-requests.confirm', $yeuCau->ma_yeu_cau), [
                'ma_dap_ung' => $dapUng->ma_dap_ung,
            ]);
        $confirmResponse->assertRedirect();
        $confirmResponse->assertSessionHas('success');

        // Kiểm tra trạng thái yêu cầu chuyển sang CHO_GIAI_NGAN
        $yeuCau->refresh();
        $this->assertEquals(TrangThaiYeuCauTaiLieu::CHO_GIAI_NGAN, $yeuCau->trang_thai);

        // Admin duyệt giải ngân điểm Bounty
        $approveAction = app(\App\Modules\Bounties\Application\Actions\ApproveBountyPayoutAction::class);
        $approveResult = $approveAction->execute($this->admin, $yeuCau->ma_yeu_cau);
        $this->assertTrue($approveResult['success']);

        // Kiểm tra trạng thái yêu cầu sau khi duyệt
        $yeuCau->refresh();
        $this->assertEquals(TrangThaiYeuCauTaiLieu::DA_HOAN_THANH, $yeuCau->trang_thai);

        // Kiểm tra ví Student B nhận 50 điểm
        $viB = $this->studentB->viDiem->fresh();
        $this->assertEquals(150, $viB->diem_kha_dung);
    }
}
