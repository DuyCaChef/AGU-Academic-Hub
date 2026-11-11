<?php

namespace Tests\Feature;

use App\Enums\LoaiGiaoDichDiem;
use App\Enums\TrangThaiTaiLieu;
use App\Enums\TrangThaiYeuCauChinhSua;
use App\Enums\TrangThaiYeuCauTaiLieu;
use App\Enums\VaiTroNguoiDung;
use App\Models\DapUngYeuCauTaiLieu;
use App\Models\GiaoDichDiem;
use App\Models\Khoa;
use App\Models\LanNopTaiLieuThayThe;
use App\Models\MonHoc;
use App\Models\NguoiDung;
use App\Models\TaiLieu;
use App\Models\ViDiem;
use App\Models\YeuCauChinhSuaDongGop;
use App\Models\YeuCauTaiLieu;
use App\Modules\Bounties\Application\Actions\ApproveBountyPayoutAction;
use App\Shared\Services\ReputationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PostBountyCorrectionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected ReputationService $reputationService;
    protected Khoa $khoa;
    protected MonHoc $monHoc;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->reputationService = app(ReputationService::class);
        $this->khoa = Khoa::create(['ten_khoa' => 'Khoa Cong Nghe Thong Tin', 'ky_hieu_khoa' => 'CNTT']);
        $this->monHoc = MonHoc::factory()->create(['ma_khoa' => $this->khoa->ma_khoa]);
    }

    // Helper: Tạo người dùng với số dư ví chỉ định
    protected function createUser(VaiTroNguoiDung $role = VaiTroNguoiDung::SINH_VIEN, int $points = 100): NguoiDung
    {
        $user = NguoiDung::factory()->create([
            'ma_khoa' => $this->khoa->ma_khoa,
            'vai_tro' => $role,
        ]);
        $user->viDiem->update([
            'diem_kha_dung' => $points,
            'diem_tam_giu'  => 0,
        ]);
        return $user;
    }

    // 1. Kiểm thử người tạo từ chối bài đóng góp kèm lý do
    public function test_creator_can_reject_contribution_with_reason(): void
    {
        $userA = $this->createUser(VaiTroNguoiDung::SINH_VIEN, 100);
        $userB = $this->createUser(VaiTroNguoiDung::SINH_VIEN, 50);

        // A tạo Bounty 40 điểm (đóng băng 40 điểm)
        $yeuCau = app(\App\Modules\Bounties\Application\Actions\CreateBountyRequestAction::class)->execute($userA, [
            'ma_mon_hoc'  => $this->monHoc->ma_mon_hoc,
            'tieu_de'     => 'Cần tìm slide bài giảng tuần 5',
            'phan_loai'   => 'BAI_GIANG',
            'diem_thuong' => 40,
        ]);

        // B đóng góp tài liệu
        $docB = TaiLieu::create([
            'ma_mon_hoc'    => $this->monHoc->ma_mon_hoc,
            'ma_nguoi_dang' => $userB->ma_nguoi_dung,
            'tieu_de'       => 'Slide tuan 5',
            'duong_dan_tep' => 'doc/slide5.pdf',
            'dinh_dang_tep' => 'pdf',
            'dung_luong_kb' => 1024,
            'ma_bam_file'   => hash('sha256', 'slide5'),
            'phan_loai'     => 'BAI_GIANG',
            'trang_thai'    => TrangThaiTaiLieu::DA_DUYET,
        ]);

        $submission = DapUngYeuCauTaiLieu::create([
            'ma_yeu_cau'        => $yeuCau->ma_yeu_cau,
            'ma_tai_lieu'       => $docB->ma_tai_lieu,
            'ma_nguoi_dong_gop' => $userB->ma_nguoi_dung,
            'trang_thai'        => 'CHO_DUYET',
        ]);

        // Người lạ hoặc B không thể tự từ chối
        $userC = $this->createUser();
        $this->actingAs($userC)
            ->post(route('document-requests.reject-fulfillment', $yeuCau->ma_yeu_cau), [
                'ma_dap_ung' => $submission->ma_dap_ung,
                'ly_do'      => 'Tài liệu sai nội dung',
            ])
            ->assertSessionHas('error');

        // A (chủ Bounty) từ chối bài nộp kèm lý do
        $response = $this->actingAs($userA)
            ->post(route('document-requests.reject-fulfillment', $yeuCau->ma_yeu_cau), [
                'ma_dap_ung' => $submission->ma_dap_ung,
                'ly_do'      => 'Slide này bị thiếu 10 trang bài tập cuối chương',
            ]);

        $response->assertRedirect();
        $submission->refresh();
        $this->assertEquals('TU_CHOI', $submission->trang_thai);
        $this->assertEquals('Slide này bị thiếu 10 trang bài tập cuối chương', $submission->ly_do_nguoi_tao_tu_choi);

        // Bounty vẫn mở và điểm vẫn được đóng băng trong ví A
        $yeuCau->refresh();
        $this->assertEquals(TrangThaiYeuCauTaiLieu::MO, $yeuCau->trang_thai);
        $this->assertEquals(40, $userA->viDiem->fresh()->diem_tam_giu);
        $this->assertEquals(60, $userA->viDiem->fresh()->diem_kha_dung);
    }

    // 2. Kiểm thử luồng tạo yêu cầu chỉnh sửa sau khi Bounty hoàn thành
    public function test_create_correction_request_on_completed_bounty(): void
    {
        $userA = $this->createUser(VaiTroNguoiDung::SINH_VIEN, 100);
        $userB = $this->createUser(VaiTroNguoiDung::SINH_VIEN, 50);
        $admin = $this->createUser(VaiTroNguoiDung::QUAN_TRI_VIEN);

        // 1. Tạo và hoàn thành Bounty
        $yeuCau = app(\App\Modules\Bounties\Application\Actions\CreateBountyRequestAction::class)->execute($userA, [
            'ma_mon_hoc'  => $this->monHoc->ma_mon_hoc,
            'tieu_de'     => 'Cần tìm đề cương ôn tập CSDL',
            'phan_loai'   => 'DE_THI',
            'diem_thuong' => 50,
        ]);

        $docB = TaiLieu::create([
            'ma_mon_hoc'    => $this->monHoc->ma_mon_hoc,
            'ma_nguoi_dang' => $userB->ma_nguoi_dung,
            'tieu_de'       => 'De cuong CSDL',
            'duong_dan_tep' => 'doc/csdl.pdf',
            'dinh_dang_tep' => 'pdf',
            'dung_luong_kb' => 512,
            'ma_bam_file'   => hash('sha256', 'csdl'),
            'phan_loai'     => 'DE_THI',
            'trang_thai'    => TrangThaiTaiLieu::DA_DUYET,
        ]);

        $submission = DapUngYeuCauTaiLieu::create([
            'ma_yeu_cau'        => $yeuCau->ma_yeu_cau,
            'ma_tai_lieu'       => $docB->ma_tai_lieu,
            'ma_nguoi_dong_gop' => $userB->ma_nguoi_dung,
            'trang_thai'        => 'CHO_DUYET',
        ]);

        // A xác nhận
        $this->actingAs($userA)->post(route('document-requests.confirm', $yeuCau->ma_yeu_cau), [
            'ma_dap_ung' => $submission->ma_dap_ung,
        ]);

        // Admin duyệt giải ngân -> Bounty DA_HOAN_THANH
        app(ApproveBountyPayoutAction::class)->execute($admin, $yeuCau->ma_yeu_cau);
        $yeuCau->refresh();
        $this->assertEquals(TrangThaiYeuCauTaiLieu::DA_HOAN_THANH, $yeuCau->trang_thai);

        // 2. Không cho phép người khác ngoài A tạo yêu cầu chỉnh sửa
        $userC = $this->createUser();
        $this->actingAs($userC)
            ->post(route('document-requests.corrections.store', $yeuCau->ma_yeu_cau), [
                'ly_do_chinh_sua' => 'Yêu cầu bổ sung đáp án câu 4',
            ])
            ->assertSessionHas('error');

        // 3. A tạo yêu cầu chỉnh sửa hợp lệ
        $response = $this->actingAs($userA)
            ->post(route('document-requests.corrections.store', $yeuCau->ma_yeu_cau), [
                'ly_do_chinh_sua' => 'Phần giải thích câu 4 và câu 5 bị mờ, bạn bổ sung bản scan rõ hơn nhé!',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('yeu_cau_chinh_sua_dong_gop', [
            'ma_yeu_cau'        => $yeuCau->ma_yeu_cau,
            'ma_dap_ung'        => $submission->ma_dap_ung,
            'ma_nguoi_yeu_cau'  => $userA->ma_nguoi_dung,
            'ma_nguoi_dong_gop' => $userB->ma_nguoi_dung,
            'trang_thai'        => 'CHO_XU_LY',
        ]);

        // 4. Tuyệt đối không có giao dịch điểm phát sinh khi tạo yêu cầu chỉnh sửa
        $this->assertEquals(100, $userB->viDiem->fresh()->diem_kha_dung);
        $this->assertEquals(50, $userA->viDiem->fresh()->diem_kha_dung);

        // 5. Chặn tạo yêu cầu chỉnh sửa trùng lặp khi đang có yêu cầu hoạt động
        $this->actingAs($userA)
            ->post(route('document-requests.corrections.store', $yeuCau->ma_yeu_cau), [
                'ly_do_chinh_sua' => 'Tạo thêm một yêu cầu chỉnh sửa khác',
            ])
            ->assertSessionHas('error');
    }

    // 3. Kiểm thử người đóng góp từ chối yêu cầu chỉnh sửa
    public function test_contributor_can_reject_correction_request(): void
    {
        $userA = $this->createUser(VaiTroNguoiDung::SINH_VIEN, 100);
        $userB = $this->createUser(VaiTroNguoiDung::SINH_VIEN, 50);
        $admin = $this->createUser(VaiTroNguoiDung::QUAN_TRI_VIEN);

        $yeuCau = app(\App\Modules\Bounties\Application\Actions\CreateBountyRequestAction::class)->execute($userA, [
            'ma_mon_hoc'  => $this->monHoc->ma_mon_hoc,
            'tieu_de'     => 'CSDL Nang Cao',
            'phan_loai'   => 'BAI_GIANG',
            'diem_thuong' => 30,
        ]);

        $docB = TaiLieu::create([
            'ma_mon_hoc'    => $this->monHoc->ma_mon_hoc,
            'ma_nguoi_dang' => $userB->ma_nguoi_dung,
            'tieu_de'       => 'Doc B',
            'duong_dan_tep' => 'doc/b.pdf',
            'dinh_dang_tep' => 'pdf',
            'dung_luong_kb' => 100,
            'ma_bam_file'   => hash('sha256', 'b'),
            'phan_loai'     => 'BAI_GIANG',
            'trang_thai'    => TrangThaiTaiLieu::DA_DUYET,
        ]);

        $submission = DapUngYeuCauTaiLieu::create([
            'ma_yeu_cau'        => $yeuCau->ma_yeu_cau,
            'ma_tai_lieu'       => $docB->ma_tai_lieu,
            'ma_nguoi_dong_gop' => $userB->ma_nguoi_dung,
            'trang_thai'        => 'CHO_DUYET',
        ]);

        $this->actingAs($userA)->post(route('document-requests.confirm', $yeuCau->ma_yeu_cau), [
            'ma_dap_ung' => $submission->ma_dap_ung,
        ]);
        app(ApproveBountyPayoutAction::class)->execute($admin, $yeuCau->ma_yeu_cau);

        // A tạo yêu cầu chỉnh sửa
        $this->actingAs($userA)->post(route('document-requests.corrections.store', $yeuCau->ma_yeu_cau), [
            'ly_do_chinh_sua' => 'Cần bổ sung bài tập chương 3',
        ]);

        $yccs = YeuCauChinhSuaDongGop::first();

        // B từ chối chỉnh sửa
        $response = $this->actingAs($userB)->post(route('corrections.respond', $yccs->ma_yeu_cau_chinh_sua), [
            'hanh_dong'     => 'TU_CHOI',
            'ly_do_tu_choi' => 'Hiện tại mình đã thi xong và không còn giữ bản lưu chương 3.',
        ]);

        $response->assertRedirect();
        $yccs->refresh();
        $this->assertEquals(TrangThaiYeuCauChinhSua::TU_CHOI, $yccs->trang_thai);
        $this->assertEquals('Hiện tại mình đã thi xong và không còn giữ bản lưu chương 3.', $yccs->ly_do_tu_choi);

        // Điểm thưởng và trạng thái Bounty giữ nguyên
        $yeuCau->refresh();
        $this->assertEquals(TrangThaiYeuCauTaiLieu::DA_HOAN_THANH, $yeuCau->trang_thai);
        $this->assertEquals(80, $userB->viDiem->fresh()->diem_kha_dung);
        $this->assertEquals(70, $userA->viDiem->fresh()->diem_kha_dung);
    }

    // 4. Kiểm thử người đóng góp chấp nhận, nộp nhiều phiên bản thay thế và A nghiệm thu hoàn thành
    public function test_full_correction_replacement_and_confirmation_workflow(): void
    {
        $userA = $this->createUser(VaiTroNguoiDung::SINH_VIEN, 100);
        $userB = $this->createUser(VaiTroNguoiDung::SINH_VIEN, 50);
        $admin = $this->createUser(VaiTroNguoiDung::QUAN_TRI_VIEN);

        $yeuCau = app(\App\Modules\Bounties\Application\Actions\CreateBountyRequestAction::class)->execute($userA, [
            'ma_mon_hoc'  => $this->monHoc->ma_mon_hoc,
            'tieu_de'     => 'Học máy nâng cao',
            'phan_loai'   => 'DO_AN',
            'diem_thuong' => 50,
        ]);

        $docB = TaiLieu::create([
            'ma_mon_hoc'    => $this->monHoc->ma_mon_hoc,
            'ma_nguoi_dang' => $userB->ma_nguoi_dung,
            'tieu_de'       => 'Source code v1',
            'duong_dan_tep' => 'doc/v1.zip',
            'dinh_dang_tep' => 'zip',
            'dung_luong_kb' => 1024,
            'ma_bam_file'   => hash('sha256', 'v1'),
            'phan_loai'     => 'DO_AN',
            'trang_thai'    => TrangThaiTaiLieu::DA_DUYET,
        ]);

        $submission = DapUngYeuCauTaiLieu::create([
            'ma_yeu_cau'        => $yeuCau->ma_yeu_cau,
            'ma_tai_lieu'       => $docB->ma_tai_lieu,
            'ma_nguoi_dong_gop' => $userB->ma_nguoi_dung,
            'trang_thai'        => 'CHO_DUYET',
        ]);

        $this->actingAs($userA)->post(route('document-requests.confirm', $yeuCau->ma_yeu_cau), [
            'ma_dap_ung' => $submission->ma_dap_ung,
        ]);
        app(ApproveBountyPayoutAction::class)->execute($admin, $yeuCau->ma_yeu_cau);

        // A tạo yêu cầu chỉnh sửa
        $this->actingAs($userA)->post(route('document-requests.corrections.store', $yeuCau->ma_yeu_cau), [
            'ly_do_chinh_sua' => 'Thiếu file requirements.txt và hướng dẫn cài đặt',
        ]);

        $yccs = YeuCauChinhSuaDongGop::first();

        // 1. B chấp nhận yêu cầu chỉnh sửa
        $this->actingAs($userB)->post(route('corrections.respond', $yccs->ma_yeu_cau_chinh_sua), [
            'hanh_dong' => 'CHAP_NHAN',
        ]);

        $yccs->refresh();
        $this->assertEquals(TrangThaiYeuCauChinhSua::DANG_XU_LY, $yccs->trang_thai);

        // 2. B tải lên phiên bản thay thế lần 1
        $file1 = UploadedFile::fake()->create('source_v2.zip', 2048, 'application/zip');
        $this->actingAs($userB)->post(route('corrections.submit-replacement', $yccs->ma_yeu_cau_chinh_sua), [
            'file'    => $file1,
            'tieu_de' => 'Source code v2 kem requirements',
            'ghi_chu' => 'Đã thêm requirements.txt',
        ])->assertRedirect();

        $lan1 = LanNopTaiLieuThayThe::first();
        $this->assertNotNull($lan1);
        $this->assertFalse($lan1->la_phien_ban_duoc_chap_nhan);

        // File gốc cũ vẫn tồn tại nguyên vẹn!
        $this->assertDatabaseHas('tai_lieu', ['ma_tai_lieu' => $docB->ma_tai_lieu]);

        // 3. B nộp thêm phiên bản thay thế lần 2 (tinh chỉnh thêm)
        $file2 = UploadedFile::fake()->create('source_v2_final.zip', 2100, 'application/zip');
        $this->actingAs($userB)->post(route('corrections.submit-replacement', $yccs->ma_yeu_cau_chinh_sua), [
            'file'    => $file2,
            'tieu_de' => 'Source code v2 final kem Dockerfile',
            'ghi_chu' => 'Bổ sung thêm Dockerfile chạy 1 lệnh là xong',
        ])->assertRedirect();

        $this->assertEquals(2, $yccs->lanNopThayThes()->count());
        $lan2 = LanNopTaiLieuThayThe::latest('ma_lan_nop_thay_the')->first();

        // 4. A xem và xác nhận nghiệm thu phiên bản lần 2
        $confirmResponse = $this->actingAs($userA)->post(route('corrections.confirm-completion', $yccs->ma_yeu_cau_chinh_sua), [
            'ma_lan_nop_thay_the' => $lan2->ma_lan_nop_thay_the,
        ]);

        $confirmResponse->assertRedirect();

        // Kiểm tra phiên bản 2 được đánh dấu là bản được chấp nhận
        $lan2->refresh();
        $this->assertTrue($lan2->la_phien_ban_duoc_chap_nhan);
        $lan1->refresh();
        $this->assertFalse($lan1->la_phien_ban_duoc_chap_nhan);

        // Yêu cầu chỉnh sửa chuyển sang HOAN_THANH
        $yccs->refresh();
        $this->assertEquals(TrangThaiYeuCauChinhSua::HOAN_THANH, $yccs->trang_thai);

        // Tài liệu hiển thị mặc định của Bounty cập nhật sang tài liệu của bản 2
        $yeuCau->refresh();
        $this->assertEquals($lan2->ma_tai_lieu, $yeuCau->ma_tai_lieu_dap_ung);
        $this->assertEquals(TrangThaiYeuCauTaiLieu::DA_HOAN_THANH, $yeuCau->trang_thai);

        // Tuyệt đối không phát sinh giao dịch điểm ví nào mới!
        $this->assertEquals(100, $userB->viDiem->fresh()->diem_kha_dung);
        $this->assertEquals(50, $userA->viDiem->fresh()->diem_kha_dung);
    }

    // 5. Kiểm thử bình đẳng quyền giữa Sinh viên và Cựu sinh viên (Alumni Parity)
    public function test_alumni_has_full_bounty_and_correction_parity(): void
    {
        $alumniA = $this->createUser(VaiTroNguoiDung::CUU_SINH_VIEN, 150);
        $alumniB = $this->createUser(VaiTroNguoiDung::CUU_SINH_VIEN, 80);

        // Alumni A tạo Bounty
        $yeuCau = app(\App\Modules\Bounties\Application\Actions\CreateBountyRequestAction::class)->execute($alumniA, [
            'ma_mon_hoc'  => $this->monHoc->ma_mon_hoc,
            'tieu_de'     => 'Kinh nghiem do an tot nghiep CNTT',
            'phan_loai'   => 'DO_AN',
            'diem_thuong' => 50,
        ]);

        $this->assertEquals(100, $alumniA->viDiem->fresh()->diem_kha_dung);
        $this->assertEquals(50, $alumniA->viDiem->fresh()->diem_tam_giu);

        // Alumni B đóng góp tài liệu từ máy tính
        $file = UploadedFile::fake()->create('kinh_nghiem.pdf', 500, 'application/pdf');
        $fulfillResult = app(\App\Modules\Bounties\Application\Actions\FulfillBountyRequestAction::class)->execute(
            $alumniB,
            $yeuCau->ma_yeu_cau,
            ['tieu_de' => 'Chia se kinh nghiem DATN', 'phan_loai' => 'DO_AN'],
            $file
        );

        $this->assertTrue($fulfillResult['success']);
        $this->assertDatabaseHas('dap_ung_yeu_cau_tai_lieu', [
            'ma_yeu_cau'        => $yeuCau->ma_yeu_cau,
            'ma_nguoi_dong_gop' => $alumniB->ma_nguoi_dung,
        ]);
    }

    // 6. Kiểm thử ngăn chặn giải ngân hai lần (Double Payout Concurrency Protection)
    public function test_double_payout_concurrency_protection(): void
    {
        $userA = $this->createUser(VaiTroNguoiDung::SINH_VIEN, 100);
        $userB = $this->createUser(VaiTroNguoiDung::SINH_VIEN, 20);
        $admin1 = $this->createUser(VaiTroNguoiDung::QUAN_TRI_VIEN);
        $mod2   = $this->createUser(VaiTroNguoiDung::KIEM_DUYET_VIEN);

        $yeuCau = app(\App\Modules\Bounties\Application\Actions\CreateBountyRequestAction::class)->execute($userA, [
            'ma_mon_hoc'  => $this->monHoc->ma_mon_hoc,
            'tieu_de'     => 'An toan mang Bounty',
            'phan_loai'   => 'BAI_GIANG',
            'diem_thuong' => 50,
        ]);

        $docB = TaiLieu::create([
            'ma_mon_hoc'    => $this->monHoc->ma_mon_hoc,
            'ma_nguoi_dang' => $userB->ma_nguoi_dung,
            'tieu_de'       => 'Slide An toan mang',
            'duong_dan_tep' => 'doc/atm.pdf',
            'dinh_dang_tep' => 'pdf',
            'dung_luong_kb' => 300,
            'ma_bam_file'   => hash('sha256', 'atm'),
            'phan_loai'     => 'BAI_GIANG',
            'trang_thai'    => TrangThaiTaiLieu::DA_DUYET,
        ]);

        $submission = DapUngYeuCauTaiLieu::create([
            'ma_yeu_cau'        => $yeuCau->ma_yeu_cau,
            'ma_tai_lieu'       => $docB->ma_tai_lieu,
            'ma_nguoi_dong_gop' => $userB->ma_nguoi_dung,
            'trang_thai'        => 'CHO_DUYET',
        ]);

        // A xác nhận -> chuyển CHO_GIAI_NGAN
        $this->actingAs($userA)->post(route('document-requests.confirm', $yeuCau->ma_yeu_cau), [
            'ma_dap_ung' => $submission->ma_dap_ung,
        ]);

        $action = app(ApproveBountyPayoutAction::class);

        // Lần duyệt 1: Admin 1 duyệt thành công
        $res1 = $action->execute($admin1, $yeuCau->ma_yeu_cau);
        $this->assertTrue($res1['success']);

        // Lần duyệt 2: Mod 2 cố duyệt lại cùng Bounty đó -> Bị chặn đứng ngay!
        $res2 = $action->execute($mod2, $yeuCau->ma_yeu_cau);
        $this->assertFalse($res2['success']);
        $this->assertStringContainsString('không ở trạng thái chờ duyệt giải ngân', $res2['message']);

        // Xác minh chỉ có DUY NHẤT 1 giao dịch giải ngân cộng điểm cho B
        $payoutTxCount = GiaoDichDiem::where('loai_tham_chieu', YeuCauTaiLieu::class)
            ->where('ma_tham_chieu', $yeuCau->ma_yeu_cau)
            ->where('loai_giao_dich', LoaiGiaoDichDiem::CONG_DIEM)
            ->count();

        $this->assertEquals(1, $payoutTxCount);

        // Số dư ví B chỉ được cộng chính xác 1 lần (20 + 50 = 70 điểm)
        $this->assertEquals(70, $userB->viDiem->fresh()->diem_kha_dung);
        $this->assertEquals(50, $userA->viDiem->fresh()->diem_kha_dung);
        $this->assertEquals(0, $userA->viDiem->fresh()->diem_tam_giu);
    }
}

