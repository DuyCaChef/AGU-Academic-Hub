<?php

namespace Tests\Feature;

use App\Enums\LoaiGiaoDichDiem;
use App\Enums\TrangThaiNguoiDung;
use App\Enums\TrangThaiTaiLieu;
use App\Enums\TrangThaiYeuCauTaiLieu;
use App\Enums\VaiTroNguoiDung;
use App\Models\DapUngYeuCauTaiLieu;
use App\Models\GiaoDichDiem;
use App\Models\Khoa;
use App\Models\MonHoc;
use App\Models\NguoiDung;
use App\Models\TaiLieu;
use App\Models\ViDiem;
use App\Models\YeuCauTaiLieu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class Phase3b3CourseWishlistTest extends TestCase
{
    use RefreshDatabase;

    protected Khoa $faculty;
    protected MonHoc $courseA;
    protected MonHoc $courseB;
    protected NguoiDung $studentA;
    protected NguoiDung $studentB;
    protected NguoiDung $alumniUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->faculty = Khoa::create([
            'ten_khoa'     => 'Khoa Công nghệ Thông tin',
            'ky_hieu_khoa' => 'CNTT',
        ]);

        $this->courseA = MonHoc::create([
            'ma_khoa'     => $this->faculty->ma_khoa,
            'ma_hoc_phan' => 'COS101',
            'ten_mon_hoc' => 'Lập trình Căn bản',
            'so_tin_chi'  => 3,
            'mo_ta'       => 'Học phần cơ sở lập trình đại cương.',
        ]);

        $this->courseB = MonHoc::create([
            'ma_khoa'     => $this->faculty->ma_khoa,
            'ma_hoc_phan' => 'COS102',
            'ten_mon_hoc' => 'Cấu trúc Dữ liệu',
            'so_tin_chi'  => 3,
            'mo_ta'       => 'Cấu trúc dữ liệu và giải thuật.',
        ]);

        // Student A (Người tạo yêu cầu tìm tài liệu)
        $this->studentA = NguoiDung::factory()->create([
            'ho_ten'      => 'Nguyễn Văn Sinh Viên A',
            'ma_khoa'     => $this->faculty->ma_khoa,
            'vai_tro'     => VaiTroNguoiDung::SINH_VIEN,
            'diem_uy_tin' => 100,
        ]);

        // Student B (Người đóng góp tài liệu săn thưởng)
        $this->studentB = NguoiDung::factory()->create([
            'ho_ten'      => 'Trần Thị Người Đóng Góp B',
            'ma_khoa'     => $this->faculty->ma_khoa,
            'vai_tro'     => VaiTroNguoiDung::SINH_VIEN,
            'diem_uy_tin' => 50,
        ]);

        // Alumni User
        $this->alumniUser = NguoiDung::factory()->create([
            'ho_ten'      => 'Lê Cựu Sinh Viên',
            'ma_khoa'     => $this->faculty->ma_khoa,
            'vai_tro'     => VaiTroNguoiDung::CUU_SINH_VIEN,
            'diem_uy_tin' => 120,
        ]);
    }

    // =========================================================================
    // NHÓM A: DANH SÁCH & PHÂN LẬP MÔN HỌC (AC-01, AC-02, AC-03, AC-04, AC-05)
    // =========================================================================

    public function test_ac01_course_detail_page_includes_wishlist_tab_and_props(): void
    {
        $response = $this->actingAs($this->studentA)
            ->get("/mon-hoc/{$this->courseA->ma_hoc_phan}");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Courses/Show')
            ->has('course')
            ->has('documents')
            ->has('reviews')
            ->has('thong_ke')
            ->has('wishlist_requests')
            ->has('filters')
            ->where('course.ma_hoc_phan', 'COS101')
        );
    }

    public function test_ac02_ac03_wishlist_requests_are_isolated_to_current_course(): void
    {
        // Yêu cầu thuộc môn A
        $reqA = YeuCauTaiLieu::create([
            'ma_mon_hoc'   => $this->courseA->ma_mon_hoc,
            'ma_nguoi_tao' => $this->studentA->ma_nguoi_dung,
            'tieu_de'      => 'Cần tìm đề thi cuối kỳ môn COS101',
            'phan_loai'    => 'DE_THI',
            'diem_thuong'  => 30,
            'trang_thai'   => TrangThaiYeuCauTaiLieu::MO,
        ]);

        // Yêu cầu thuộc môn B
        $reqB = YeuCauTaiLieu::create([
            'ma_mon_hoc'   => $this->courseB->ma_mon_hoc,
            'ma_nguoi_tao' => $this->studentB->ma_nguoi_dung,
            'tieu_de'      => 'Cần tìm slide bài giảng môn COS102',
            'phan_loai'    => 'BAI_GIANG',
            'diem_thuong'  => 50,
            'trang_thai'   => TrangThaiYeuCauTaiLieu::MO,
        ]);

        $response = $this->actingAs($this->studentA)
            ->get("/mon-hoc/{$this->courseA->ma_hoc_phan}?tab=wishlist");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Courses/Show')
            ->has('wishlist_requests.data', 1)
            ->where('wishlist_requests.data.0.ma_yeu_cau', $reqA->ma_yeu_cau)
            ->where('wishlist_requests.data.0.tieu_de', 'Cần tìm đề thi cuối kỳ môn COS101')
        );
    }

    public function test_ac04_wishlist_request_item_contains_required_metadata(): void
    {
        $req = YeuCauTaiLieu::create([
            'ma_mon_hoc'     => $this->courseA->ma_mon_hoc,
            'ma_nguoi_tao'   => $this->studentA->ma_nguoi_dung,
            'tieu_de'        => 'Tìm tài liệu hướng dẫn thực hành C++',
            'mo_ta'          => 'Tài liệu các bài lab từ tuần 1 đến tuần 10',
            'ten_giang_vien' => 'TS. Trần Văn Thầy',
            'phan_loai'      => 'BAI_GIANG',
            'diem_thuong'    => 40,
            'trang_thai'     => TrangThaiYeuCauTaiLieu::MO,
        ]);

        $response = $this->actingAs($this->studentA)
            ->get("/mon-hoc/{$this->courseA->ma_hoc_phan}?tab=wishlist");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Courses/Show')
            ->where('wishlist_requests.data.0.tieu_de', 'Tìm tài liệu hướng dẫn thực hành C++')
            ->where('wishlist_requests.data.0.mo_ta', 'Tài liệu các bài lab từ tuần 1 đến tuần 10')
            ->where('wishlist_requests.data.0.ten_giang_vien', 'TS. Trần Văn Thầy')
            ->where('wishlist_requests.data.0.phan_loai', 'BAI_GIANG')
            ->where('wishlist_requests.data.0.diem_thuong', 40)
            ->where('wishlist_requests.data.0.trang_thai', 'MO')
            ->has('wishlist_requests.data.0.nguoi_tao')
            ->has('wishlist_requests.data.0.dap_ungs_count')
        );
    }

    public function test_ac05_course_detail_shows_empty_state_when_no_requests(): void
    {
        $response = $this->actingAs($this->studentA)
            ->get("/mon-hoc/{$this->courseA->ma_hoc_phan}?tab=wishlist");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Courses/Show')
            ->has('wishlist_requests.data', 0)
            ->where('wishlist_requests.total', 0)
        );
    }

    public function test_wishlist_filter_by_status_and_category(): void
    {
        // Request 1: Đang mở, Đề thi
        YeuCauTaiLieu::create([
            'ma_mon_hoc'   => $this->courseA->ma_mon_hoc,
            'ma_nguoi_tao' => $this->studentA->ma_nguoi_dung,
            'tieu_de'      => 'Tìm đề thi giữa kỳ năm 2024',
            'phan_loai'    => 'DE_THI',
            'diem_thuong'  => 25,
            'trang_thai'   => TrangThaiYeuCauTaiLieu::MO,
        ]);

        // Request 2: Đã hoàn thành, Bài giảng
        YeuCauTaiLieu::create([
            'ma_mon_hoc'   => $this->courseA->ma_mon_hoc,
            'ma_nguoi_tao' => $this->studentA->ma_nguoi_dung,
            'tieu_de'      => 'Tìm giáo trình môn cơ sở',
            'phan_loai'    => 'BAI_GIANG',
            'diem_thuong'  => 50,
            'trang_thai'   => TrangThaiYeuCauTaiLieu::DA_HOAN_THANH,
        ]);

        // Lọc theo trạng thái MO
        $responseOpen = $this->actingAs($this->studentA)
            ->get("/mon-hoc/{$this->courseA->ma_hoc_phan}?tab=wishlist&wishlist_status=MO");

        $responseOpen->assertStatus(200);
        $responseOpen->assertInertia(fn (Assert $page) => $page
            ->has('wishlist_requests.data', 1)
            ->where('wishlist_requests.data.0.trang_thai', 'MO')
        );

        // Lọc theo phân loại BAI_GIANG
        $responseCategory = $this->actingAs($this->studentA)
            ->get("/mon-hoc/{$this->courseA->ma_hoc_phan}?tab=wishlist&wishlist_category=BAI_GIANG");

        $responseCategory->assertStatus(200);
        $responseCategory->assertInertia(fn (Assert $page) => $page
            ->has('wishlist_requests.data', 1)
            ->where('wishlist_requests.data.0.phan_loai', 'BAI_GIANG')
        );
    }

    // =========================================================================
    // NHÓM B: TẠO YÊU CẦU & VALIDATION (AC-06, AC-07, AC-08, AC-09, AC-19)
    // =========================================================================

    public function test_ac06_ac10_ac11_authenticated_student_can_create_bounty_request_for_course(): void
    {
        $walletBefore = $this->studentA->viDiem;
        $this->assertEquals(100, $walletBefore->diem_kha_dung);
        $this->assertEquals(0, $walletBefore->diem_tam_giu);

        $response = $this->actingAs($this->studentA)
            ->from("/mon-hoc/{$this->courseA->ma_hoc_phan}")
            ->post(route('document-requests.store'), [
                'ma_mon_hoc'     => $this->courseA->ma_mon_hoc,
                'tieu_de'        => 'Cần tìm trọn bộ đề thi thực hành C++',
                'mo_ta'          => 'Đề thi của các khóa gần đây',
                'ten_giang_vien' => 'ThS. Nguyễn Văn A',
                'phan_loai'      => 'DE_THI',
                'diem_thuong'    => 40,
                'redirect_to'    => 'back',
            ]);

        $response->assertRedirect("/mon-hoc/{$this->courseA->ma_hoc_phan}");

        // Kiểm tra database có record yêu cầu
        $this->assertDatabaseHas('yeu_cau_tai_lieu', [
            'ma_mon_hoc'   => $this->courseA->ma_mon_hoc,
            'ma_nguoi_tao' => $this->studentA->ma_nguoi_dung,
            'tieu_de'      => 'Cần tìm trọn bộ đề thi thực hành C++',
            'diem_thuong'  => 40,
            'trang_thai'   => TrangThaiYeuCauTaiLieu::MO->value,
        ]);

        // Kiểm tra ví điểm đã đóng băng 40 điểm
        $walletAfter = $this->studentA->viDiem->fresh();
        $this->assertEquals(60, $walletAfter->diem_kha_dung);
        $this->assertEquals(40, $walletAfter->diem_tam_giu);

        // Kiểm tra nhật ký giao dịch điểm
        $this->assertDatabaseHas('giao_dich_diem', [
            'ma_vi'             => $walletAfter->ma_vi,
            'loai_giao_dich'    => LoaiGiaoDichDiem::TAM_GIU->value,
            'so_diem'           => 40,
            'thay_doi_kha_dung' => -40,
            'thay_doi_tam_giu'  => 40,
        ]);
    }

    public function test_ac07_alumni_can_also_create_bounty_request(): void
    {
        $response = $this->actingAs($this->alumniUser)
            ->from("/mon-hoc/{$this->courseA->ma_hoc_phan}")
            ->post(route('document-requests.store'), [
                'ma_mon_hoc'     => $this->courseA->ma_mon_hoc,
                'tieu_de'        => 'Cần tài liệu tổng hợp đồ án tốt nghiệp cũ',
                'mo_ta'          => 'Phục vụ nghiên cứu tham khảo',
                'phan_loai'      => 'DO_AN',
                'diem_thuong'    => 50,
                'redirect_to'    => 'back',
            ]);

        $response->assertRedirect("/mon-hoc/{$this->courseA->ma_hoc_phan}");

        $this->assertDatabaseHas('yeu_cau_tai_lieu', [
            'ma_mon_hoc'   => $this->courseA->ma_mon_hoc,
            'ma_nguoi_tao' => $this->alumniUser->ma_nguoi_dung,
            'diem_thuong'  => 50,
        ]);

        $this->assertEquals(50, $this->alumniUser->viDiem->fresh()->diem_tam_giu);
    }

    public function test_ac08_cannot_create_bounty_request_exceeding_available_wallet_points(): void
    {
        // Student có 100 điểm, thử cọc 150 điểm
        $response = $this->actingAs($this->studentA)
            ->from("/mon-hoc/{$this->courseA->ma_hoc_phan}")
            ->post(route('document-requests.store'), [
                'ma_mon_hoc'  => $this->courseA->ma_mon_hoc,
                'tieu_de'     => 'Cần tìm đề thi với điểm thưởng cực cao',
                'phan_loai'   => 'DE_THI',
                'diem_thuong' => 150,
                'redirect_to' => 'back',
            ]);

        $response->assertSessionHasErrors('diem_thuong');

        // Đảm bảo không tạo yêu cầu và ví không bị trừ
        $this->assertDatabaseMissing('yeu_cau_tai_lieu', [
            'tieu_de' => 'Cần tìm đề thi với điểm thưởng cực cao',
        ]);
        $this->assertEquals(100, $this->studentA->viDiem->fresh()->diem_kha_dung);
        $this->assertEquals(0, $this->studentA->viDiem->fresh()->diem_tam_giu);
    }

    public function test_ac09_cannot_create_bounty_request_below_minimum_points(): void
    {
        // Điểm thưởng tối thiểu theo quy định là 10
        $response = $this->actingAs($this->studentA)
            ->from("/mon-hoc/{$this->courseA->ma_hoc_phan}")
            ->post(route('document-requests.store'), [
                'ma_mon_hoc'  => $this->courseA->ma_mon_hoc,
                'tieu_de'     => 'Cần tìm đề thi với điểm thưởng quá ít',
                'phan_loai'   => 'DE_THI',
                'diem_thuong' => 5,
                'redirect_to' => 'back',
            ]);

        $response->assertSessionHasErrors('diem_thuong');
    }

    public function test_ac19_guest_cannot_create_or_fulfill_bounty_request(): void
    {
        $responseStore = $this->post(route('document-requests.store'), [
            'ma_mon_hoc'  => $this->courseA->ma_mon_hoc,
            'tieu_de'     => 'Yêu cầu không hợp lệ từ khách',
            'phan_loai'   => 'DE_THI',
            'diem_thuong' => 20,
        ]);
        $responseStore->assertRedirect(route('login'));

        $responseFulfill = $this->post(route('document-requests.fulfill', 1), [
            'ma_tai_lieu' => 1,
        ]);
        $responseFulfill->assertRedirect(route('login'));
    }

    // =========================================================================
    // NHÓM C: ĐÓNG BĂNG, HOÀN ĐIỂM & QUẢN LÝ HỦY (AC-11, AC-16, AC-17, AC-18)
    // =========================================================================

    public function test_ac16_author_can_cancel_open_request_and_refund_frozen_points(): void
    {
        // Tạo yêu cầu qua store để tự động đóng băng 35 điểm
        $this->actingAs($this->studentA)->post(route('document-requests.store'), [
            'ma_mon_hoc'  => $this->courseA->ma_mon_hoc,
            'tieu_de'     => 'Yêu cầu sắp bị hủy để thử nghiệm',
            'phan_loai'   => 'BAI_TAP',
            'diem_thuong' => 35,
        ]);

        $yeuCau = YeuCauTaiLieu::first();
        $this->assertEquals(65, $this->studentA->viDiem->fresh()->diem_kha_dung);
        $this->assertEquals(35, $this->studentA->viDiem->fresh()->diem_tam_giu);

        // Người tạo gửi request hủy
        $responseCancel = $this->actingAs($this->studentA)
            ->from("/mon-hoc/{$this->courseA->ma_hoc_phan}")
            ->delete(route('document-requests.destroy', $yeuCau->ma_yeu_cau), [
                'redirect_to' => 'back',
            ]);

        $responseCancel->assertRedirect("/mon-hoc/{$this->courseA->ma_hoc_phan}");

        // Kiểm tra trạng thái yêu cầu
        $this->assertEquals(TrangThaiYeuCauTaiLieu::DA_HUY, $yeuCau->fresh()->trang_thai);

        // Kiểm tra ví điểm đã hoàn lại 100% điểm
        $wallet = $this->studentA->viDiem->fresh();
        $this->assertEquals(100, $wallet->diem_kha_dung);
        $this->assertEquals(0, $wallet->diem_tam_giu);

        // Kiểm tra nhật ký giao dịch HOAN_TRA
        $this->assertDatabaseHas('giao_dich_diem', [
            'ma_vi'             => $wallet->ma_vi,
            'loai_giao_dich'    => LoaiGiaoDichDiem::HOAN_TRA->value,
            'so_diem'           => 35,
            'thay_doi_kha_dung' => 35,
            'thay_doi_tam_giu'  => -35,
        ]);
    }

    public function test_ac17_non_creator_cannot_cancel_request(): void
    {
        // Student A tạo yêu cầu
        $this->actingAs($this->studentA)->post(route('document-requests.store'), [
            'ma_mon_hoc'  => $this->courseA->ma_mon_hoc,
            'tieu_de'     => 'Yêu cầu của sinh viên A',
            'phan_loai'   => 'DE_THI',
            'diem_thuong' => 30,
        ]);

        $yeuCau = YeuCauTaiLieu::first();

        // Student B cố tình gửi lệnh hủy -> 403 Forbidden
        $response = $this->actingAs($this->studentB)
            ->delete(route('document-requests.destroy', $yeuCau->ma_yeu_cau));

        $response->assertStatus(403);
        $this->assertEquals(TrangThaiYeuCauTaiLieu::MO, $yeuCau->fresh()->trang_thai);
    }

    public function test_ac17_cannot_cancel_already_completed_request(): void
    {
        $yeuCau = YeuCauTaiLieu::create([
            'ma_mon_hoc'   => $this->courseA->ma_mon_hoc,
            'ma_nguoi_tao' => $this->studentA->ma_nguoi_dung,
            'tieu_de'      => 'Yêu cầu đã hoàn thành',
            'phan_loai'    => 'DE_THI',
            'diem_thuong'  => 30,
            'trang_thai'   => TrangThaiYeuCauTaiLieu::DA_HOAN_THANH,
        ]);

        $response = $this->actingAs($this->studentA)
            ->delete(route('document-requests.destroy', $yeuCau->ma_yeu_cau));

        $response->assertSessionHas('error');
    }

    // =========================================================================
    // NHÓM D: FULFILLMENT & GIẢI NGÂN BOUNTY (AC-12, AC-13, AC-14, AC-15)
    // =========================================================================

    public function test_ac12_contributor_can_submit_fulfillment_with_approved_document(): void
    {
        // Tạo yêu cầu của Student A
        $yeuCau = YeuCauTaiLieu::create([
            'ma_mon_hoc'   => $this->courseA->ma_mon_hoc,
            'ma_nguoi_tao' => $this->studentA->ma_nguoi_dung,
            'tieu_de'      => 'Cần tìm bài giảng lý thuyết',
            'phan_loai'    => 'BAI_GIANG',
            'diem_thuong'  => 50,
            'trang_thai'   => TrangThaiYeuCauTaiLieu::MO,
        ]);

        // Tạo tài liệu cá nhân đã được duyệt của Student B
        $docB = TaiLieu::create([
            'ma_mon_hoc'    => $this->courseA->ma_mon_hoc,
            'ma_nguoi_dang' => $this->studentB->ma_nguoi_dung,
            'tieu_de'       => 'Tài liệu bài giảng slide chuẩn',
            'duong_dan_tep' => 'documents/slide_chuan.pdf',
            'dinh_dang_tep' => 'pdf',
            'dung_luong_kb' => 200,
            'ma_bam_file'   => hash('sha256', 'slide_content'),
            'phan_loai'     => 'BAI_GIANG',
            'luot_tai'      => 5,
            'trang_thai'    => TrangThaiTaiLieu::DA_DUYET->value,
        ]);

        // Student B gửi đề xuất tài liệu phản hồi yêu cầu
        $response = $this->actingAs($this->studentB)
            ->post(route('document-requests.fulfill', $yeuCau->ma_yeu_cau), [
                'ma_tai_lieu'      => $docB->ma_tai_lieu,
                'ghi_chu_dong_gop' => 'Mình gửi bạn tài liệu slide đầy đủ nhé!',
            ]);

        $response->assertSessionHas('success');

        // Bản ghi đáp ứng được tạo
        $this->assertDatabaseHas('dap_ung_yeu_cau_tai_lieu', [
            'ma_yeu_cau'        => $yeuCau->ma_yeu_cau,
            'ma_tai_lieu'       => $docB->ma_tai_lieu,
            'ma_nguoi_dong_gop' => $this->studentB->ma_nguoi_dung,
            'trang_thai'        => 'CHO_DUYET',
            'ghi_chu_dong_gop'  => 'Mình gửi bạn tài liệu slide đầy đủ nhé!',
        ]);
    }

    public function test_ac13_author_cannot_fulfill_own_bounty_request(): void
    {
        // Yêu cầu của Student A
        $yeuCau = YeuCauTaiLieu::create([
            'ma_mon_hoc'   => $this->courseA->ma_mon_hoc,
            'ma_nguoi_tao' => $this->studentA->ma_nguoi_dung,
            'tieu_de'      => 'Cần tìm tài liệu tự treo thưởng',
            'phan_loai'    => 'BAI_GIANG',
            'diem_thuong'  => 50,
            'trang_thai'   => TrangThaiYeuCauTaiLieu::MO,
        ]);

        // Tài liệu cũng của Student A
        $docA = TaiLieu::create([
            'ma_mon_hoc'    => $this->courseA->ma_mon_hoc,
            'ma_nguoi_dang' => $this->studentA->ma_nguoi_dung,
            'tieu_de'       => 'Tài liệu của chính tôi',
            'duong_dan_tep' => 'documents/my_own_doc.pdf',
            'dinh_dang_tep' => 'pdf',
            'dung_luong_kb' => 150,
            'ma_bam_file'   => hash('sha256', 'own_doc_content'),
            'phan_loai'     => 'BAI_GIANG',
            'luot_tai'      => 0,
            'trang_thai'    => TrangThaiTaiLieu::DA_DUYET->value,
        ]);

        // Student A cố tình nộp tài liệu vào yêu cầu của chính mình
        $response = $this->actingAs($this->studentA)
            ->post(route('document-requests.fulfill', $yeuCau->ma_yeu_cau), [
                'ma_tai_lieu' => $docA->ma_tai_lieu,
            ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('dap_ung_yeu_cau_tai_lieu', [
            'ma_yeu_cau'  => $yeuCau->ma_yeu_cau,
            'ma_tai_lieu' => $docA->ma_tai_lieu,
        ]);
    }

    public function test_ac14_ac15_creator_can_confirm_fulfillment_disbursing_bounty_and_reputation(): void
    {
        // 1. Student A tạo yêu cầu với 50 điểm
        $this->actingAs($this->studentA)->post(route('document-requests.store'), [
            'ma_mon_hoc'  => $this->courseA->ma_mon_hoc,
            'tieu_de'     => 'Cần tìm bài tập lớn giải thuật',
            'phan_loai'   => 'DO_AN',
            'diem_thuong' => 50,
        ]);

        $yeuCau = YeuCauTaiLieu::first();

        // 2. Student B đóng góp tài liệu
        $docB = TaiLieu::create([
            'ma_mon_hoc'    => $this->courseA->ma_mon_hoc,
            'ma_nguoi_dang' => $this->studentB->ma_nguoi_dung,
            'tieu_de'       => 'Bài tập lớn mẫu',
            'duong_dan_tep' => 'documents/btl.pdf',
            'dinh_dang_tep' => 'pdf',
            'dung_luong_kb' => 300,
            'ma_bam_file'   => hash('sha256', 'btl_content'),
            'phan_loai'     => 'DO_AN',
            'luot_tai'      => 10,
            'trang_thai'    => TrangThaiTaiLieu::DA_DUYET->value,
        ]);

        $dapUng = DapUngYeuCauTaiLieu::create([
            'ma_yeu_cau'        => $yeuCau->ma_yeu_cau,
            'ma_tai_lieu'       => $docB->ma_tai_lieu,
            'ma_nguoi_dong_gop' => $this->studentB->ma_nguoi_dung,
            'trang_thai'        => 'CHO_DUYET',
        ]);

        $reputationBefore = $this->studentB->diem_uy_tin; // 50

        // 3. Student A xác nhận nghiệm thu
        $responseConfirm = $this->actingAs($this->studentA)
            ->post(route('document-requests.confirm', $yeuCau->ma_yeu_cau), [
                'ma_dap_ung' => $dapUng->ma_dap_ung,
            ]);

        $responseConfirm->assertSessionHas('success');

        // Yêu cầu chuyển sang CHO_GIAI_NGAN chờ Admin/Mod phê duyệt
        $this->assertEquals(TrangThaiYeuCauTaiLieu::CHO_GIAI_NGAN, $yeuCau->fresh()->trang_thai);
        $this->assertEquals('NGUOI_YEU_CAU_XAC_NHAN', $dapUng->fresh()->trang_thai);

        // 4. Admin phê duyệt giải ngân Bounty
        $admin = NguoiDung::factory()->create([
            'ma_khoa' => $this->faculty->ma_khoa,
            'vai_tro' => 'QUAN_TRI_VIEN',
        ]);
        $approveAction = app(\App\Modules\Bounties\Application\Actions\ApproveBountyPayoutAction::class);
        $approveResult = $approveAction->execute($admin, $yeuCau->ma_yeu_cau);
        $this->assertTrue($approveResult['success']);

        // Yêu cầu chuyển sang DA_HOAN_THANH
        $this->assertEquals(TrangThaiYeuCauTaiLieu::DA_HOAN_THANH, $yeuCau->fresh()->trang_thai);

        // Bài nộp chuyển sang CHAP_NHAN
        $this->assertEquals('CHAP_NHAN', $dapUng->fresh()->trang_thai);

        // Ví người tạo: điểm tạm giữ trở về 0
        $walletA = $this->studentA->viDiem->fresh();
        $this->assertEquals(50, $walletA->diem_kha_dung);
        $this->assertEquals(0, $walletA->diem_tam_giu);

        // Ví người đóng góp: được cộng 50 điểm khả dụng (100 -> 150)
        $walletB = $this->studentB->viDiem->fresh();
        $this->assertEquals(150, $walletB->diem_kha_dung);

        // Điểm uy tín của người đóng góp tăng 50 (50 -> 100)
        $this->assertEquals($reputationBefore + 50, $this->studentB->fresh()->diem_uy_tin);

        // Kiểm tra nhật ký giao dịch GIAI_NGAN
        $this->assertDatabaseHas('giao_dich_diem', [
            'ma_vi'             => $walletB->ma_vi,
            'loai_giao_dich'    => LoaiGiaoDichDiem::CONG_DIEM->value,
            'so_diem'           => 50,
            'thay_doi_kha_dung' => 50,
        ]);
    }

    // =========================================================================
    // NHÓM E: REGRESSION & INTEGRATION (AC-20, AC-21)
    // =========================================================================

    public function test_ac20_course_show_preserves_documents_and_reviews_data(): void
    {
        // Tạo 1 tài liệu cho môn học
        TaiLieu::create([
            'ma_mon_hoc'    => $this->courseA->ma_mon_hoc,
            'ma_nguoi_dang' => $this->studentA->ma_nguoi_dung,
            'tieu_de'       => 'Tài liệu kiểm tra regression',
            'duong_dan_tep' => 'documents/reg.pdf',
            'dinh_dang_tep' => 'pdf',
            'dung_luong_kb' => 100,
            'ma_bam_file'   => hash('sha256', 'reg_content'),
            'phan_loai'     => 'BAI_GIANG',
            'luot_tai'      => 0,
            'trang_thai'    => TrangThaiTaiLieu::DA_DUYET->value,
        ]);

        $response = $this->actingAs($this->studentA)
            ->get("/mon-hoc/{$this->courseA->ma_hoc_phan}?tab=wishlist");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Courses/Show')
            ->has('course')
            ->has('faculties')
            ->has('documents.data', 1)
            ->has('reviews')
            ->has('thong_ke')
            ->has('wishlist_requests')
            ->where('filters.tab', 'wishlist')
        );
    }
}
