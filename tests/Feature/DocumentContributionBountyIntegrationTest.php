<?php

namespace Tests\Feature;

use App\Enums\LoaiGiaoDichDiem;
use App\Enums\TrangThaiTaiLieu;
use App\Enums\TrangThaiYeuCauTaiLieu;
use App\Enums\VaiTroNguoiDung;
use App\Models\DapUngYeuCauTaiLieu;
use App\Models\Khoa;
use App\Models\MonHoc;
use App\Models\NguoiDung;
use App\Models\TaiLieu;
use App\Models\ViDiem;
use App\Models\YeuCauTaiLieu;
use App\Modules\Bounties\Infrastructure\Persistence\WishlistBountyRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentContributionBountyIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected Khoa $faculty;
    protected MonHoc $courseA;
    protected MonHoc $courseB;
    protected NguoiDung $requester;
    protected NguoiDung $contributor;
    protected NguoiDung $otherStudent;
    protected NguoiDung $moderator;
    protected NguoiDung $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->faculty = Khoa::create([
            'ten_khoa' => 'Khoa Công nghệ Thông tin',
            'ky_hieu_khoa' => 'CNTT',
        ]);

        $this->courseA = MonHoc::create([
            'ma_khoa' => $this->faculty->ma_khoa,
            'ma_hoc_phan' => 'COS101',
            'ten_mon_hoc' => 'Lập trình Cơ bản',
            'so_tin_chi' => 3,
            'mo_ta' => 'Môn học cơ bản đại cương',
        ]);

        $this->courseB = MonHoc::create([
            'ma_khoa' => $this->faculty->ma_khoa,
            'ma_hoc_phan' => 'COS102',
            'ten_mon_hoc' => 'Cơ sở Dữ liệu',
            'so_tin_chi' => 3,
            'mo_ta' => 'Môn học cơ sở dữ liệu',
        ]);

        // 1. Người tạo yêu cầu săn thưởng (Requester - Student A)
        $this->requester = NguoiDung::factory()->create([
            'ho_ten' => 'Nguyễn Văn Requester',
            'ma_khoa' => $this->faculty->ma_khoa,
            'vai_tro' => VaiTroNguoiDung::SINH_VIEN,
            'diem_uy_tin' => 100,
        ]);

        // 2. Người đóng góp tài liệu săn thưởng (Contributor - Student B)
        $this->contributor = NguoiDung::factory()->create([
            'ho_ten' => 'Trần Thị Contributor',
            'ma_khoa' => $this->faculty->ma_khoa,
            'vai_tro' => VaiTroNguoiDung::SINH_VIEN,
            'diem_uy_tin' => 50,
        ]);

        // 3. Người dùng sinh viên khác (Other Student C)
        $this->otherStudent = NguoiDung::factory()->create([
            'ho_ten' => 'Lê Văn Other',
            'ma_khoa' => $this->faculty->ma_khoa,
            'vai_tro' => VaiTroNguoiDung::SINH_VIEN,
            'diem_uy_tin' => 50,
        ]);

        // 4. Kiểm duyệt viên (Moderator)
        $this->moderator = NguoiDung::factory()->create([
            'ho_ten' => 'Phạm Văn Kiểm Duyệt Viên',
            'ma_khoa' => $this->faculty->ma_khoa,
            'vai_tro' => VaiTroNguoiDung::KIEM_DUYET_VIEN,
        ]);

        // 5. Quản trị viên (Admin)
        $this->admin = NguoiDung::factory()->create([
            'ho_ten' => 'Hoàng Quản Trị Viên',
            'ma_khoa' => $this->faculty->ma_khoa,
            'vai_tro' => VaiTroNguoiDung::QUAN_TRI_VIEN,
        ]);
    }

    // =========================================================================
    // 1. BUG FIX VERIFICATION: SQLSTATE[42S22] ten_tep_goc Column Not Found
    // =========================================================================

    public function test_wishlist_repository_eager_loading_loads_tai_lieu_without_sql_column_error(): void
    {
        $yeuCau = YeuCauTaiLieu::create([
            'ma_mon_hoc' => $this->courseA->ma_mon_hoc,
            'ma_nguoi_tao' => $this->requester->ma_nguoi_dung,
            'tieu_de' => 'Cần tìm slide bài giảng lập trình',
            'phan_loai' => 'BAI_GIANG',
            'diem_thuong' => 30,
            'trang_thai' => TrangThaiYeuCauTaiLieu::MO,
        ]);

        $taiLieu = TaiLieu::create([
            'ma_mon_hoc' => $this->courseA->ma_mon_hoc,
            'ma_nguoi_dang' => $this->contributor->ma_nguoi_dung,
            'tieu_de' => 'Slide bài giảng chuẩn',
            'duong_dan_tep' => 'documents/slide.pdf',
            'dinh_dang_tep' => 'pdf',
            'dung_luong_kb' => 120,
            'ma_bam_file' => hash('sha256', 'sample_slide_content'),
            'phan_loai' => 'BAI_GIANG',
            'luot_tai' => 0,
            'trang_thai' => TrangThaiTaiLieu::DA_DUYET->value,
        ]);

        $dapUng = DapUngYeuCauTaiLieu::create([
            'ma_yeu_cau' => $yeuCau->ma_yeu_cau,
            'ma_tai_lieu' => $taiLieu->ma_tai_lieu,
            'ma_nguoi_dong_gop' => $this->contributor->ma_nguoi_dung,
            'trang_thai' => 'CHO_DUYET',
        ]);

        /** @var WishlistBountyRepository $repository */
        $repository = app(WishlistBountyRepository::class);

        // findRequestById must eager load dapUngs.taiLieu without SQL 1054 error
        $loadedRequest = $repository->findRequestById($yeuCau->ma_yeu_cau);
        $this->assertNotNull($loadedRequest);
        $this->assertCount(1, $loadedRequest->dapUngs);

        $loadedTaiLieu = $loadedRequest->dapUngs->first()->taiLieu;
        $this->assertNotNull($loadedTaiLieu);
        $this->assertEquals($taiLieu->ma_tai_lieu, $loadedTaiLieu->ma_tai_lieu);
        $this->assertEquals('Slide bài giảng chuẩn', $loadedTaiLieu->tieu_de);
        $this->assertEquals(TrangThaiTaiLieu::DA_DUYET->value, $loadedTaiLieu->trang_thai);

        // findSubmission must also eager load taiLieu without error
        $loadedSubmission = $repository->findSubmission($yeuCau->ma_yeu_cau, $dapUng->ma_dap_ung);
        $this->assertNotNull($loadedSubmission);
        $this->assertNotNull($loadedSubmission->taiLieu);
        $this->assertEquals($taiLieu->ma_tai_lieu, $loadedSubmission->taiLieu->ma_tai_lieu);
    }

    // =========================================================================
    // 2. AUD-H01 VERIFICATION: Bounty Fulfillment Validation & Ownership Rules
    // =========================================================================

    public function test_cannot_fulfill_bounty_with_another_users_document(): void
    {
        $yeuCau = YeuCauTaiLieu::create([
            'ma_mon_hoc' => $this->courseA->ma_mon_hoc,
            'ma_nguoi_tao' => $this->requester->ma_nguoi_dung,
            'tieu_de' => 'Cần tìm đề thi mẫu',
            'phan_loai' => 'DE_THI',
            'diem_thuong' => 40,
            'trang_thai' => TrangThaiYeuCauTaiLieu::MO,
        ]);

        // Document belongs to otherStudent
        $docOther = TaiLieu::create([
            'ma_mon_hoc' => $this->courseA->ma_mon_hoc,
            'ma_nguoi_dang' => $this->otherStudent->ma_nguoi_dung,
            'tieu_de' => 'Đề thi của Other Student',
            'duong_dan_tep' => 'documents/other.pdf',
            'dinh_dang_tep' => 'pdf',
            'dung_luong_kb' => 80,
            'ma_bam_file' => hash('sha256', 'other_content'),
            'phan_loai' => 'DE_THI',
            'luot_tai' => 0,
            'trang_thai' => TrangThaiTaiLieu::DA_DUYET->value,
        ]);

        // Contributor attempts to submit other's document
        $response = $this->actingAs($this->contributor)
            ->post(route('document-requests.fulfill', $yeuCau->ma_yeu_cau), [
                'ma_tai_lieu' => $docOther->ma_tai_lieu,
            ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('dap_ung_yeu_cau_tai_lieu', [
            'ma_yeu_cau' => $yeuCau->ma_yeu_cau,
            'ma_nguoi_dong_gop' => $this->contributor->ma_nguoi_dung,
        ]);
    }

    public function test_cannot_fulfill_bounty_with_unapproved_document_method_a(): void
    {
        $yeuCau = YeuCauTaiLieu::create([
            'ma_mon_hoc' => $this->courseA->ma_mon_hoc,
            'ma_nguoi_tao' => $this->requester->ma_nguoi_dung,
            'tieu_de' => 'Cần tìm đề thi cuối kỳ',
            'phan_loai' => 'DE_THI',
            'diem_thuong' => 30,
            'trang_thai' => TrangThaiYeuCauTaiLieu::MO,
        ]);

        // Contributor's document is still pending moderation (CHO_DUYET)
        $docPending = TaiLieu::create([
            'ma_mon_hoc' => $this->courseA->ma_mon_hoc,
            'ma_nguoi_dang' => $this->contributor->ma_nguoi_dung,
            'tieu_de' => 'Đề thi chưa duyệt',
            'duong_dan_tep' => 'documents/pending.pdf',
            'dinh_dang_tep' => 'pdf',
            'dung_luong_kb' => 80,
            'ma_bam_file' => hash('sha256', 'pending_content'),
            'phan_loai' => 'DE_THI',
            'luot_tai' => 0,
            'trang_thai' => TrangThaiTaiLieu::CHO_DUYET->value,
        ]);

        $response = $this->actingAs($this->contributor)
            ->post(route('document-requests.fulfill', $yeuCau->ma_yeu_cau), [
                'ma_tai_lieu' => $docPending->ma_tai_lieu,
            ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('dap_ung_yeu_cau_tai_lieu', [
            'ma_yeu_cau' => $yeuCau->ma_yeu_cau,
            'ma_tai_lieu' => $docPending->ma_tai_lieu,
        ]);
    }

    public function test_cannot_fulfill_bounty_with_document_from_different_course(): void
    {
        $yeuCau = YeuCauTaiLieu::create([
            'ma_mon_hoc' => $this->courseA->ma_mon_hoc, // Course A
            'ma_nguoi_tao' => $this->requester->ma_nguoi_dung,
            'tieu_de' => 'Cần tìm tài liệu Course A',
            'phan_loai' => 'BAI_GIANG',
            'diem_thuong' => 25,
            'trang_thai' => TrangThaiYeuCauTaiLieu::MO,
        ]);

        // Contributor has approved doc for Course B
        $docCourseB = TaiLieu::create([
            'ma_mon_hoc' => $this->courseB->ma_mon_hoc, // Course B!
            'ma_nguoi_dang' => $this->contributor->ma_nguoi_dung,
            'tieu_de' => 'Tài liệu Course B',
            'duong_dan_tep' => 'documents/b.pdf',
            'dinh_dang_tep' => 'pdf',
            'dung_luong_kb' => 80,
            'ma_bam_file' => hash('sha256', 'course_b_content'),
            'phan_loai' => 'BAI_GIANG',
            'luot_tai' => 0,
            'trang_thai' => TrangThaiTaiLieu::DA_DUYET->value,
        ]);

        $response = $this->actingAs($this->contributor)
            ->post(route('document-requests.fulfill', $yeuCau->ma_yeu_cau), [
                'ma_tai_lieu' => $docCourseB->ma_tai_lieu,
            ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('dap_ung_yeu_cau_tai_lieu', [
            'ma_yeu_cau' => $yeuCau->ma_yeu_cau,
            'ma_tai_lieu' => $docCourseB->ma_tai_lieu,
        ]);
    }

    public function test_cannot_fulfill_same_bounty_twice_with_same_document(): void
    {
        $yeuCau = YeuCauTaiLieu::create([
            'ma_mon_hoc' => $this->courseA->ma_mon_hoc,
            'ma_nguoi_tao' => $this->requester->ma_nguoi_dung,
            'tieu_de' => 'Cần tìm tài liệu chống spam',
            'phan_loai' => 'BAI_GIANG',
            'diem_thuong' => 30,
            'trang_thai' => TrangThaiYeuCauTaiLieu::MO,
        ]);

        $doc = TaiLieu::create([
            'ma_mon_hoc' => $this->courseA->ma_mon_hoc,
            'ma_nguoi_dang' => $this->contributor->ma_nguoi_dung,
            'tieu_de' => 'Tài liệu hợp lệ',
            'duong_dan_tep' => 'documents/valid.pdf',
            'dinh_dang_tep' => 'pdf',
            'dung_luong_kb' => 100,
            'ma_bam_file' => hash('sha256', 'valid_content'),
            'phan_loai' => 'BAI_GIANG',
            'luot_tai' => 0,
            'trang_thai' => TrangThaiTaiLieu::DA_DUYET->value,
        ]);

        // First fulfillment succeeds
        $firstResponse = $this->actingAs($this->contributor)
            ->post(route('document-requests.fulfill', $yeuCau->ma_yeu_cau), [
                'ma_tai_lieu' => $doc->ma_tai_lieu,
                'ghi_chu_dong_gop' => 'Gửi lần 1',
            ]);
        $firstResponse->assertSessionHas('success');

        // Second fulfillment with identical document must be rejected
        $secondResponse = $this->actingAs($this->contributor)
            ->post(route('document-requests.fulfill', $yeuCau->ma_yeu_cau), [
                'ma_tai_lieu' => $doc->ma_tai_lieu,
                'ghi_chu_dong_gop' => 'Gửi lần 2 trùng lặp',
            ]);
        $secondResponse->assertSessionHas('error');
    }

    // =========================================================================
    // 3. AUD-H01 VERIFICATION: Bounty Disbursement Guard (No Points On Unapproved)
    // =========================================================================

    public function test_requester_cannot_confirm_disbursement_if_submitted_document_is_unapproved(): void
    {
        // Requester creates bounty with 50 points frozen
        $this->actingAs($this->requester)->post(route('document-requests.store'), [
            'ma_mon_hoc' => $this->courseA->ma_mon_hoc,
            'tieu_de' => 'Cần tìm đồ án môn học',
            'phan_loai' => 'DO_AN',
            'diem_thuong' => 50,
        ]);

        $yeuCau = YeuCauTaiLieu::first();
        $this->assertEquals(50, $this->requester->viDiem->fresh()->diem_tam_giu);

        // Document submitted via local upload or pending moderation
        $unapprovedDoc = TaiLieu::create([
            'ma_mon_hoc' => $this->courseA->ma_mon_hoc,
            'ma_nguoi_dang' => $this->contributor->ma_nguoi_dung,
            'tieu_de' => 'Đồ án đang chờ duyệt',
            'duong_dan_tep' => 'documents/do_an_cho_duyet.pdf',
            'dinh_dang_tep' => 'pdf',
            'dung_luong_kb' => 500,
            'ma_bam_file' => hash('sha256', 'unapproved_project_content'),
            'phan_loai' => 'DO_AN',
            'luot_tai' => 0,
            'trang_thai' => TrangThaiTaiLieu::CHO_DUYET->value,
        ]);

        $dapUng = DapUngYeuCauTaiLieu::create([
            'ma_yeu_cau' => $yeuCau->ma_yeu_cau,
            'ma_tai_lieu' => $unapprovedDoc->ma_tai_lieu,
            'ma_nguoi_dong_gop' => $this->contributor->ma_nguoi_dung,
            'trang_thai' => 'CHO_DUYET',
        ]);

        // Requester tries to confirm disbursement for unapproved document
        $response = $this->actingAs($this->requester)
            ->post(route('document-requests.confirm', $yeuCau->ma_yeu_cau), [
                'ma_dap_ung' => $dapUng->ma_dap_ung,
            ]);

        $response->assertSessionHas('error');

        // Ensure state has NOT changed: request remains open, submission remains pending, points remain frozen
        $this->assertEquals(TrangThaiYeuCauTaiLieu::MO, $yeuCau->fresh()->trang_thai);
        $this->assertEquals('CHO_DUYET', $dapUng->fresh()->trang_thai);
        $this->assertEquals(50, $this->requester->viDiem->fresh()->diem_tam_giu);
        $this->assertEquals(100, $this->contributor->viDiem->fresh()->diem_kha_dung); // Default 100, no disbursement!
    }

    public function test_requester_can_confirm_disbursement_once_document_is_approved(): void
    {
        $this->actingAs($this->requester)->post(route('document-requests.store'), [
            'ma_mon_hoc' => $this->courseA->ma_mon_hoc,
            'tieu_de' => 'Cần tìm đồ án giải thuật',
            'phan_loai' => 'DO_AN',
            'diem_thuong' => 50,
        ]);

        $yeuCau = YeuCauTaiLieu::first();

        $doc = TaiLieu::create([
            'ma_mon_hoc' => $this->courseA->ma_mon_hoc,
            'ma_nguoi_dang' => $this->contributor->ma_nguoi_dung,
            'tieu_de' => 'Đồ án đã được duyệt',
            'duong_dan_tep' => 'documents/do_an_da_duyet.pdf',
            'dinh_dang_tep' => 'pdf',
            'dung_luong_kb' => 500,
            'ma_bam_file' => hash('sha256', 'approved_project_content'),
            'phan_loai' => 'DO_AN',
            'luot_tai' => 0,
            'trang_thai' => TrangThaiTaiLieu::DA_DUYET->value,
        ]);

        $dapUng = DapUngYeuCauTaiLieu::create([
            'ma_yeu_cau' => $yeuCau->ma_yeu_cau,
            'ma_tai_lieu' => $doc->ma_tai_lieu,
            'ma_nguoi_dong_gop' => $this->contributor->ma_nguoi_dung,
            'trang_thai' => 'CHO_DUYET',
        ]);

        $response = $this->actingAs($this->requester)
            ->post(route('document-requests.confirm', $yeuCau->ma_yeu_cau), [
                'ma_dap_ung' => $dapUng->ma_dap_ung,
            ]);

        $response->assertSessionHas('success');

        // Bước 1: SV A xác nhận, yêu cầu chuyển sang CHO_GIAI_NGAN để Admin/Mod thẩm duyệt
        $this->assertEquals(TrangThaiYeuCauTaiLieu::CHO_GIAI_NGAN, $yeuCau->fresh()->trang_thai);
        $this->assertEquals('NGUOI_YEU_CAU_XAC_NHAN', $dapUng->fresh()->trang_thai);

        // Bước 2: Admin/Kiểm duyệt viên phê duyệt giải ngân điểm thưởng Bounty
        $adminResponse = $this->actingAs($this->admin)
            ->put(route('admin.bounties.approve-payout', $yeuCau->ma_yeu_cau));

        $adminResponse->assertSessionHas('success');

        $this->assertEquals(TrangThaiYeuCauTaiLieu::DA_HOAN_THANH, $yeuCau->fresh()->trang_thai);
        $this->assertEquals('CHAP_NHAN', $dapUng->fresh()->trang_thai);
        $this->assertEquals(0, $this->requester->viDiem->fresh()->diem_tam_giu);
        $this->assertEquals(150, $this->contributor->viDiem->fresh()->diem_kha_dung); // 100 + 50
    }

    // =========================================================================
    // 4. AUD-H02 VERIFICATION: Security for Unapproved Document View / Preview / Download
    // =========================================================================

    public function test_unrelated_user_or_guest_cannot_view_unapproved_document_detail(): void
    {
        $unapprovedDoc = TaiLieu::create([
            'ma_mon_hoc' => $this->courseA->ma_mon_hoc,
            'ma_nguoi_dang' => $this->contributor->ma_nguoi_dung,
            'tieu_de' => 'Tài liệu đang chờ kiểm duyệt',
            'duong_dan_tep' => 'documents/pending_detail.pdf',
            'dinh_dang_tep' => 'pdf',
            'dung_luong_kb' => 100,
            'ma_bam_file' => hash('sha256', 'pending_detail_content'),
            'phan_loai' => 'BAI_GIANG',
            'luot_tai' => 0,
            'trang_thai' => TrangThaiTaiLieu::CHO_DUYET->value,
        ]);

        // Unrelated student -> 403 Forbidden
        $responseStudent = $this->actingAs($this->otherStudent)
            ->get(route('documents.show', $unapprovedDoc->ma_tai_lieu));
        $responseStudent->assertStatus(403);

        // Guest -> 403 or redirect
        $responseGuest = $this->get(route('documents.show', $unapprovedDoc->ma_tai_lieu));
        $this->assertTrue(in_array($responseGuest->status(), [403, 302], true));
    }

    public function test_author_can_view_own_unapproved_document_detail(): void
    {
        $unapprovedDoc = TaiLieu::create([
            'ma_mon_hoc' => $this->courseA->ma_mon_hoc,
            'ma_nguoi_dang' => $this->contributor->ma_nguoi_dung,
            'tieu_de' => 'Tài liệu của chính tôi đang chờ duyệt',
            'duong_dan_tep' => 'documents/my_pending.pdf',
            'dinh_dang_tep' => 'pdf',
            'dung_luong_kb' => 100,
            'ma_bam_file' => hash('sha256', 'my_pending_content'),
            'phan_loai' => 'BAI_GIANG',
            'luot_tai' => 0,
            'trang_thai' => TrangThaiTaiLieu::CHO_DUYET->value,
        ]);

        $response = $this->actingAs($this->contributor)
            ->get(route('documents.show', $unapprovedDoc->ma_tai_lieu));

        $response->assertStatus(200);
    }

    public function test_admin_and_moderator_can_view_unapproved_document_detail(): void
    {
        $unapprovedDoc = TaiLieu::create([
            'ma_mon_hoc' => $this->courseA->ma_mon_hoc,
            'ma_nguoi_dang' => $this->contributor->ma_nguoi_dung,
            'tieu_de' => 'Tài liệu chờ duyệt cho staff',
            'duong_dan_tep' => 'documents/staff_pending.pdf',
            'dinh_dang_tep' => 'pdf',
            'dung_luong_kb' => 100,
            'ma_bam_file' => hash('sha256', 'staff_pending_content'),
            'phan_loai' => 'BAI_GIANG',
            'luot_tai' => 0,
            'trang_thai' => TrangThaiTaiLieu::CHO_DUYET->value,
        ]);

        // Moderator can view
        $responseMod = $this->actingAs($this->moderator)
            ->get(route('documents.show', $unapprovedDoc->ma_tai_lieu));
        $responseMod->assertStatus(200);

        // Admin can view
        $responseAdmin = $this->actingAs($this->admin)
            ->get(route('documents.show', $unapprovedDoc->ma_tai_lieu));
        $responseAdmin->assertStatus(200);
    }

    public function test_unrelated_user_cannot_preview_or_download_unapproved_document(): void
    {
        Storage::fake('local');
        $path = 'documents/unapproved_file.pdf';
        Storage::disk('local')->put($path, 'protected raw pdf bytes');

        $unapprovedDoc = TaiLieu::create([
            'ma_mon_hoc' => $this->courseA->ma_mon_hoc,
            'ma_nguoi_dang' => $this->contributor->ma_nguoi_dung,
            'tieu_de' => 'File nhạy cảm chờ duyệt',
            'duong_dan_tep' => $path,
            'dinh_dang_tep' => 'pdf',
            'dung_luong_kb' => 50,
            'ma_bam_file' => hash('sha256', 'unapproved_raw_bytes'),
            'phan_loai' => 'DE_THI',
            'luot_tai' => 0,
            'trang_thai' => TrangThaiTaiLieu::CHO_DUYET->value,
        ]);

        // Unrelated student trying preview -> 403
        $responsePreview = $this->actingAs($this->otherStudent)
            ->get(route('documents.preview', $unapprovedDoc->ma_tai_lieu));
        $responsePreview->assertStatus(403);

        // Unrelated student trying download -> 403
        $responseDownload = $this->actingAs($this->otherStudent)
            ->get(route('documents.download', $unapprovedDoc->ma_tai_lieu));
        $responseDownload->assertStatus(403);
    }

    public function test_author_and_moderator_can_preview_and_download_unapproved_document(): void
    {
        Storage::fake('local');
        $path = 'documents/staff_file.pdf';
        Storage::disk('local')->put($path, 'staff raw pdf bytes');

        $unapprovedDoc = TaiLieu::create([
            'ma_mon_hoc' => $this->courseA->ma_mon_hoc,
            'ma_nguoi_dang' => $this->contributor->ma_nguoi_dung,
            'tieu_de' => 'File kiểm duyệt nội bộ',
            'duong_dan_tep' => $path,
            'dinh_dang_tep' => 'pdf',
            'dung_luong_kb' => 50,
            'ma_bam_file' => hash('sha256', 'staff_raw_bytes'),
            'phan_loai' => 'DE_THI',
            'luot_tai' => 0,
            'trang_thai' => TrangThaiTaiLieu::CHO_DUYET->value,
        ]);

        // Author preview
        $responseAuthorPreview = $this->actingAs($this->contributor)
            ->get(route('documents.preview', $unapprovedDoc->ma_tai_lieu));
        $responseAuthorPreview->assertOk();

        // Moderator download
        $responseModDownload = $this->actingAs($this->moderator)
            ->get(route('documents.download', $unapprovedDoc->ma_tai_lieu));
        $responseModDownload->assertOk();
    }

    // =========================================================================
    // 5. METHOD B: LOCAL FILE UPLOAD INTEGRATION IN BOUNTY FULFILLMENT
    // =========================================================================

    public function test_contributor_can_fulfill_bounty_via_local_file_upload(): void
    {
        Storage::fake('local');

        $yeuCau = YeuCauTaiLieu::create([
            'ma_mon_hoc' => $this->courseA->ma_mon_hoc,
            'ma_nguoi_tao' => $this->requester->ma_nguoi_dung,
            'tieu_de' => 'Cần tìm đề thi trắc nghiệm C++',
            'phan_loai' => 'DE_THI',
            'diem_thuong' => 45,
            'trang_thai' => TrangThaiYeuCauTaiLieu::MO,
        ]);

        $fakeFile = UploadedFile::fake()->create('de_thi_cpp_2026.pdf', 350, 'application/pdf');

        $response = $this->actingAs($this->contributor)
            ->post(route('document-requests.fulfill', $yeuCau->ma_yeu_cau), [
                'file' => $fakeFile,
                'tieu_de' => 'Đề thi trắc nghiệm C++ có đáp án chi tiết',
                'phan_loai' => 'DE_THI',
                'ghi_chu_dong_gop' => 'File PDF scan chuẩn từ giáo trình môn học.',
            ]);

        $response->assertSessionHas('success');

        // Document created in database with CHO_DUYET
        $this->assertDatabaseHas('tai_lieu', [
            'ma_mon_hoc' => $this->courseA->ma_mon_hoc,
            'ma_nguoi_dang' => $this->contributor->ma_nguoi_dung,
            'tieu_de' => 'Đề thi trắc nghiệm C++ có đáp án chi tiết',
            'dinh_dang_tep' => 'pdf',
            'phan_loai' => 'DE_THI',
            'trang_thai' => TrangThaiTaiLieu::CHO_DUYET->value,
        ]);

        $createdDoc = TaiLieu::where('tieu_de', 'Đề thi trắc nghiệm C++ có đáp án chi tiết')->first();
        $this->assertNotNull($createdDoc);
        $this->assertNotEmpty($createdDoc->ma_bam_file);

        // Submission created in database with CHO_DUYET
        $this->assertDatabaseHas('dap_ung_yeu_cau_tai_lieu', [
            'ma_yeu_cau' => $yeuCau->ma_yeu_cau,
            'ma_tai_lieu' => $createdDoc->ma_tai_lieu,
            'ma_nguoi_dong_gop' => $this->contributor->ma_nguoi_dung,
            'trang_thai' => 'CHO_DUYET',
            'ghi_chu_dong_gop' => 'File PDF scan chuẩn từ giáo trình môn học.',
        ]);

        // Ensure physical file stored on disk
        $this->assertTrue(Storage::disk('local')->exists($createdDoc->duong_dan_tep));
    }

    public function test_local_file_upload_rejects_duplicate_binary_sha256(): void
    {
        Storage::fake('local');

        $fileContent = 'Unique binary content representing examination paper';
        $sha256 = hash('sha256', $fileContent);

        // Pre-existing document with same SHA-256 in the same course
        TaiLieu::create([
            'ma_mon_hoc' => $this->courseA->ma_mon_hoc,
            'ma_nguoi_dang' => $this->otherStudent->ma_nguoi_dung,
            'tieu_de' => 'Tài liệu đã tồn tại trong môn học',
            'duong_dan_tep' => 'documents/preexisting.pdf',
            'dinh_dang_tep' => 'pdf',
            'dung_luong_kb' => 100,
            'ma_bam_file' => $sha256,
            'phan_loai' => 'DE_THI',
            'luot_tai' => 0,
            'trang_thai' => TrangThaiTaiLieu::DA_DUYET->value,
        ]);

        $yeuCau = YeuCauTaiLieu::create([
            'ma_mon_hoc' => $this->courseA->ma_mon_hoc,
            'ma_nguoi_tao' => $this->requester->ma_nguoi_dung,
            'tieu_de' => 'Cần tìm đề thi',
            'phan_loai' => 'DE_THI',
            'diem_thuong' => 30,
            'trang_thai' => TrangThaiYeuCauTaiLieu::MO,
        ]);

        $duplicateFile = UploadedFile::fake()->createWithContent('duplicate.pdf', $fileContent);

        $response = $this->actingAs($this->contributor)
            ->post(route('document-requests.fulfill', $yeuCau->ma_yeu_cau), [
                'file' => $duplicateFile,
                'tieu_de' => 'Đề thi thử trùng lặp',
                'phan_loai' => 'DE_THI',
            ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('tai_lieu', [
            'tieu_de' => 'Đề thi thử trùng lặp',
        ]);
    }

    public function test_local_file_upload_enforces_ten_uploads_per_day_rate_limit(): void
    {
        Storage::fake('local');

        $yeuCau = YeuCauTaiLieu::create([
            'ma_mon_hoc' => $this->courseA->ma_mon_hoc,
            'ma_nguoi_tao' => $this->requester->ma_nguoi_dung,
            'tieu_de' => 'Cần tìm tài liệu vượt hạn mức',
            'phan_loai' => 'BAI_GIANG',
            'diem_thuong' => 20,
            'trang_thai' => TrangThaiYeuCauTaiLieu::MO,
        ]);

        // Contributor has already uploaded 10 documents today
        for ($i = 1; $i <= 10; $i++) {
            TaiLieu::create([
                'ma_mon_hoc' => $this->courseA->ma_mon_hoc,
                'ma_nguoi_dang' => $this->contributor->ma_nguoi_dung,
                'tieu_de' => "Tài liệu thứ {$i} trong ngày",
                'duong_dan_tep' => "documents/doc_{$i}.pdf",
                'dinh_dang_tep' => 'pdf',
                'dung_luong_kb' => 50,
                'ma_bam_file' => hash('sha256', "content_{$i}"),
                'phan_loai' => 'BAI_GIANG',
                'luot_tai' => 0,
                'trang_thai' => TrangThaiTaiLieu::CHO_DUYET->value,
                'created_at' => Carbon::now(),
            ]);
        }

        // 11th upload must be blocked
        $fakeFile = UploadedFile::fake()->create('doc_11.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->contributor)
            ->post(route('document-requests.fulfill', $yeuCau->ma_yeu_cau), [
                'file' => $fakeFile,
                'tieu_de' => 'Tài liệu thứ 11 cố tình tải lên',
                'phan_loai' => 'BAI_GIANG',
            ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('tai_lieu', [
            'tieu_de' => 'Tài liệu thứ 11 cố tình tải lên',
        ]);
    }

    public function test_local_file_upload_rejects_empty_file_or_invalid_extension(): void
    {
        Storage::fake('local');

        $yeuCau = YeuCauTaiLieu::create([
            'ma_mon_hoc' => $this->courseA->ma_mon_hoc,
            'ma_nguoi_tao' => $this->requester->ma_nguoi_dung,
            'tieu_de' => 'Cần tìm tài liệu',
            'phan_loai' => 'BAI_GIANG',
            'diem_thuong' => 20,
            'trang_thai' => TrangThaiYeuCauTaiLieu::MO,
        ]);

        // 1. Invalid extension (.exe)
        $exeFile = UploadedFile::fake()->create('malicious.exe', 100, 'application/x-msdownload');
        $responseExe = $this->actingAs($this->contributor)
            ->post(route('document-requests.fulfill', $yeuCau->ma_yeu_cau), [
                'file' => $exeFile,
                'tieu_de' => 'Tệp exe độc hại',
            ]);
        $responseExe->assertSessionHasErrors('file');

        // 2. Empty file (0 bytes)
        $emptyFile = UploadedFile::fake()->createWithContent('empty.pdf', '');
        $responseEmpty = $this->actingAs($this->contributor)
            ->post(route('document-requests.fulfill', $yeuCau->ma_yeu_cau), [
                'file' => $emptyFile,
                'tieu_de' => 'Tệp PDF rỗng 0 bytes',
            ]);
        $responseEmpty->assertSessionHas('error');
    }
}

