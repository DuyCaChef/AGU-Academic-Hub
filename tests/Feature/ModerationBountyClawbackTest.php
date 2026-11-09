<?php

namespace Tests\Feature;

use App\Enums\LoaiGiaoDichDiem;
use App\Enums\TrangThaiNguoiDung;
use App\Enums\TrangThaiYeuCauTaiLieu;
use App\Enums\VaiTroNguoiDung;
use App\Models\BaoCaoViPham;
use App\Models\DapUngYeuCauTaiLieu;
use App\Models\GiaoDichDiem;
use App\Models\Khoa;
use App\Models\MonHoc;
use App\Models\NguoiDung;
use App\Models\TaiLieu;
use App\Models\ViDiem;
use App\Models\YeuCauGoBoTaiLieu;
use App\Models\YeuCauTaiLieu;
use App\Modules\Administration\Domain\Enums\HanhDongKiemToan;
use App\Modules\Bounties\Application\Actions\ApproveBountyPayoutAction;
use App\Modules\Bounties\Domain\Services\ReputationEvaluator;
use App\Modules\Moderation\Application\Actions\DecideDocumentReviewAction;
use App\Modules\Moderation\Application\Actions\DecideRemovalRequestAction;
use App\Modules\Moderation\Application\Actions\ResolveViolationReportAction;
use App\Modules\Moderation\Infrastructure\Notifications\BountyPayoutClawbackRefundNotification;
use App\Modules\Moderation\Infrastructure\Notifications\BountyPayoutClawedBackNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

// Kiểm thử quy trình thu hồi điểm (Clawback) và hoàn tiền đền bù khi tài liệu Bounty đã giải ngân bị xóa do vi phạm
class ModerationBountyClawbackTest extends TestCase
{
    use RefreshDatabase;

    protected NguoiDung $moderator;
    protected NguoiDung $admin;
    protected NguoiDung $requester;
    protected NguoiDung $contributor;
    protected MonHoc $course;
    protected ReputationEvaluator $reputationEvaluator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reputationEvaluator = app(ReputationEvaluator::class);

        $khoa = Khoa::create([
            'ten_khoa'     => 'Khoa Công nghệ Thông tin',
            'ky_hieu_khoa' => 'CNTT',
            'trang_thai'   => true,
        ]);

        $this->moderator = NguoiDung::factory()->create([
            'ma_khoa'    => $khoa->ma_khoa,
            'vai_tro'    => VaiTroNguoiDung::KIEM_DUYET_VIEN,
            'ho_ten'     => 'Kiểm Duyệt Viên AGU',
            'email'      => 'mod.clawback@agu.edu.vn',
            'trang_thai' => TrangThaiNguoiDung::DANG_HOAT_DONG,
        ]);

        $this->admin = NguoiDung::factory()->create([
            'ma_khoa'    => $khoa->ma_khoa,
            'vai_tro'    => VaiTroNguoiDung::QUAN_TRI_VIEN,
            'ho_ten'     => 'Quản Trị Viên AGU',
            'email'      => 'admin.clawback@agu.edu.vn',
            'trang_thai' => TrangThaiNguoiDung::DANG_HOAT_DONG,
        ]);

        $this->requester = NguoiDung::factory()->create([
            'ma_khoa'        => $khoa->ma_khoa,
            'vai_tro'        => VaiTroNguoiDung::SINH_VIEN,
            'ho_ten'         => 'Nguyễn Văn A (Người yêu cầu)',
            'email'          => 'studentA.clawback@agu.edu.vn',
            'diem_uy_tin'    => 100,
            'so_lan_vi_pham' => 0,
            'trang_thai'     => TrangThaiNguoiDung::DANG_HOAT_DONG,
        ]);

        $this->contributor = NguoiDung::factory()->create([
            'ma_khoa'        => $khoa->ma_khoa,
            'vai_tro'        => VaiTroNguoiDung::SINH_VIEN,
            'ho_ten'         => 'Trần Văn B (Người đóng góp)',
            'email'          => 'studentB.clawback@agu.edu.vn',
            'diem_uy_tin'    => 100,
            'so_lan_vi_pham' => 0,
            'trang_thai'     => TrangThaiNguoiDung::DANG_HOAT_DONG,
        ]);

        $this->course = MonHoc::create([
            'ma_khoa'     => $khoa->ma_khoa,
            'ma_hoc_phan' => 'COS301',
            'ten_mon_hoc' => 'Cơ sở dữ liệu nâng cao',
            'so_tin_chi'  => 3,
            'mo_ta'       => 'Môn học cơ sở ngành',
            'trang_thai'  => true,
        ]);

    }

    private function createDocument(array $attributes = []): TaiLieu
    {
        return TaiLieu::create(array_merge([
            'ma_mon_hoc'        => $this->course->ma_mon_hoc,
            'ma_nguoi_dang'     => $this->contributor->ma_nguoi_dung,
            'tieu_de'           => 'Giáo trình CSDL Nâng Cao Bản Độc Quyền',
            'duong_dan_tep'     => 'documents/db_advanced.pdf',
            'dinh_dang_tep'     => 'pdf',
            'dung_luong_kb'     => 2048,
            'ma_bam_file'       => hash('sha256', uniqid('', true)),
            'phan_loai'         => 'BAI_GIANG',
            'phien_ban'         => '1.0',
            'trang_thai'        => 'DA_DUYET',
            'so_bao_cao_hop_le' => 0,
            'luot_tai'          => 15,
        ], $attributes));
    }

    // Thiết lập kịch bản Bounty đã xác nhận và giải ngân 30 điểm thành công
    private function setupDisbursedBounty(int $bountyPoints = 30): array
    {
        // 1. SV A tạo yêu cầu tìm tài liệu và đóng băng 30 điểm
        $this->reputationEvaluator->dongBangDiemBounty($this->requester, $bountyPoints);

        $yeuCau = YeuCauTaiLieu::create([
            'ma_mon_hoc'   => $this->course->ma_mon_hoc,
            'ma_nguoi_tao' => $this->requester->ma_nguoi_dung,
            'tieu_de'      => 'Cần tìm tài liệu CSDL chương 4',
            'phan_loai'    => 'BAI_GIANG',
            'diem_thuong'  => $bountyPoints,
            'trang_thai'   => TrangThaiYeuCauTaiLieu::CHO_GIAI_NGAN,
        ]);

        // 2. SV B nộp tài liệu giải quyết yêu cầu
        $document = $this->createDocument();

        $dapUng = DapUngYeuCauTaiLieu::create([
            'ma_yeu_cau'        => $yeuCau->ma_yeu_cau,
            'ma_tai_lieu'       => $document->ma_tai_lieu,
            'ma_nguoi_dong_gop' => $this->contributor->ma_nguoi_dung,
            'trang_thai'        => 'NGUOI_YEU_CAU_XAC_NHAN',
            'ghi_chu_dong_gop'  => 'Bài giảng đầy đủ của bộ môn.',
        ]);

        $yeuCau->update([
            'ma_dap_ung_duoc_chon'             => $dapUng->ma_dap_ung,
            'thoi_gian_nguoi_yeu_cau_xac_nhan' => now(),
        ]);

        // 3. Moderator duyệt giải ngân 30 điểm cho SV B thành công
        $payoutAction = app(ApproveBountyPayoutAction::class);
        $payoutAction->execute($this->moderator, $yeuCau->ma_yeu_cau);

        // Kiểm tra trạng thái sau giải ngân
        $this->assertEquals(TrangThaiYeuCauTaiLieu::DA_HOAN_THANH, $yeuCau->fresh()->trang_thai);
        $this->assertEquals('CHAP_NHAN', $dapUng->fresh()->trang_thai);
        $this->assertEquals(100 - $bountyPoints, $this->requester->fresh()->viDiem->diem_kha_dung);
        $this->assertEquals(100 + $bountyPoints, $this->contributor->fresh()->viDiem->diem_kha_dung);
        $this->assertEquals(100 + $bountyPoints, $this->contributor->fresh()->diem_uy_tin);

        return [$yeuCau, $dapUng, $document];
    }

    // 1. Moderator xóa tài liệu do vi phạm bản quyền: Thu hồi 30 điểm từ SV B và hoàn trả đền bù 30 điểm cho SV A
    public function test_moderator_deleting_document_claws_back_disbursed_bounty_and_refunds_requester(): void
    {
        Notification::fake();

        [$yeuCau, $dapUng, $document] = $this->setupDisbursedBounty(30);

        // Tác giả bản quyền phát hiện và tạo báo cáo vi phạm
        $report = BaoCaoViPham::create([
            'ma_tai_lieu'      => $document->ma_tai_lieu,
            'ma_nguoi_bao_cao' => $this->requester->ma_nguoi_dung,
            'ly_do'            => 'BAN_QUYEN',
            'chi_tiet_loi'     => 'Tài liệu sao chép trái phép sách có bản quyền.',
            'trang_thai_xu_ly' => 'CHO_XU_LY',
        ]);

        // Moderator kiểm tra và phán quyết XÓA MỀM (DELETE)
        $action = app(ResolveViolationReportAction::class);
        $action->execute($report->ma_bao_cao, 'DELETE', 'Xác nhận vi phạm bản quyền nghiêm trọng.', $this->moderator);

        // Kiểm tra tài liệu đã bị xóa mềm
        $this->assertSoftDeleted('tai_lieu', ['ma_tai_lieu' => $document->ma_tai_lieu]);

        // Kiểm tra ví SV B: Đã bị thu hồi 30 điểm (130 -> 100)
        $this->assertEquals(100, $this->contributor->fresh()->viDiem->diem_kha_dung);

        // Kiểm tra điểm uy tín của SV B: Đã bị trừ 30 điểm thưởng hoàn trả + 20 điểm phạt vi phạm bản quyền (130 -> 80)
        $this->assertEquals(80, $this->contributor->fresh()->diem_uy_tin);
        $this->assertEquals(1, $this->contributor->fresh()->so_lan_vi_pham);

        // Kiểm tra ví SV A: Đã được hoàn trả đền bù 30 điểm (70 -> 100)
        $this->assertEquals(100, $this->requester->fresh()->viDiem->diem_kha_dung);

        // Kiểm tra trạng thái bài nộp đã chuyển sang TU_CHOI
        $dapUngFresh = $dapUng->fresh();
        $this->assertEquals('TU_CHOI', $dapUngFresh->trang_thai);
        $this->assertStringContainsString('Thu hồi giải ngân', $dapUngFresh->ly_do_tu_choi_giai_ngan);

        // Kiểm tra trạng thái yêu cầu Bounty đã bị hủy
        $this->assertEquals(TrangThaiYeuCauTaiLieu::DA_HUY, $yeuCau->fresh()->trang_thai);

        // Kiểm tra lịch sử giao dịch điểm được ghi nhận đầy đủ cho cả 2 bên
        $this->assertDatabaseHas('giao_dich_diem', [
            'ma_vi'             => $this->contributor->viDiem->ma_vi,
            'loai_giao_dich'    => LoaiGiaoDichDiem::SU_DUNG->value,
            'thay_doi_kha_dung' => -30,
        ]);

        $this->assertDatabaseHas('giao_dich_diem', [
            'ma_vi'             => $this->requester->viDiem->ma_vi,
            'loai_giao_dich'    => LoaiGiaoDichDiem::HOAN_TRA->value,
            'thay_doi_kha_dung' => 30,
        ]);

        // Kiểm tra nhật ký kiểm toán ghi nhận hành vi thu hồi giải ngân
        $this->assertDatabaseHas('nhat_ky_kiem_toan', [
            'ma_nguoi_thuc_hien' => $this->moderator->ma_nguoi_dung,
            'hanh_dong'          => HanhDongKiemToan::THU_HOI_GIAI_NGAN_BOUNTY->value,
            'ma_doi_tuong'       => (string) $yeuCau->ma_yeu_cau,
        ]);

        // Kiểm tra gửi thông báo hoàn tiền cho SV A và thông báo thu hồi cho SV B
        Notification::assertSentTo($this->requester, BountyPayoutClawbackRefundNotification::class);
        Notification::assertSentTo($this->contributor, BountyPayoutClawedBackNotification::class);
    }

    // 2. Admin xử lý tài liệu trong hàng đợi kiểm tra (ReviewQueue) quyết định XÓA MỀM cũng kích hoạt quy trình Clawback
    public function test_admin_deleting_document_via_review_queue_triggers_clawback(): void
    {
        Notification::fake();

        [$yeuCau, $dapUng, $document] = $this->setupDisbursedBounty(40);

        // Admin vào hàng đợi kiểm duyệt và quyết định XÓA MỀM tài liệu
        $action = app(DecideDocumentReviewAction::class);
        $action->execute($document->ma_tai_lieu, 'DELETE', 'Admin xóa tài liệu vi phạm bản quyền.', $this->admin);

        // Kiểm tra tài liệu đã bị xóa mềm
        $this->assertSoftDeleted('tai_lieu', ['ma_tai_lieu' => $document->ma_tai_lieu]);

        // Kiểm tra ví SV B: Bị thu hồi 40 điểm (140 -> 100)
        $this->assertEquals(100, $this->contributor->fresh()->viDiem->diem_kha_dung);

        // Kiểm tra ví SV A: Được hoàn trả đền bù 40 điểm (60 -> 100)
        $this->assertEquals(100, $this->requester->fresh()->viDiem->diem_kha_dung);

        // Kiểm tra trạng thái bài nộp và yêu cầu
        $this->assertEquals('TU_CHOI', $dapUng->fresh()->trang_thai);
        $this->assertEquals(TrangThaiYeuCauTaiLieu::DA_HUY, $yeuCau->fresh()->trang_thai);

        // Nhật ký kiểm toán ghi nhận Admin thực hiện
        $this->assertDatabaseHas('nhat_ky_kiem_toan', [
            'ma_nguoi_thuc_hien' => $this->admin->ma_nguoi_dung,
            'hanh_dong'          => HanhDongKiemToan::THU_HOI_GIAI_NGAN_BOUNTY->value,
            'ma_doi_tuong'       => (string) $yeuCau->ma_yeu_cau,
        ]);
    }

    // 3. Trường hợp SV B đã tiêu bớt điểm (số dư khả dụng không đủ), thu hồi tối đa có thể (về 0) và SV A vẫn được hoàn đủ 100%
    public function test_clawback_when_contributor_has_insufficient_points_clamps_to_zero_and_refunds_requester_fully(): void
    {
        Notification::fake();

        [$yeuCau, $dapUng, $document] = $this->setupDisbursedBounty(50);

        // SV B đã dùng hết điểm, chỉ còn lại 10 điểm khả dụng
        $viB = $this->contributor->viDiem;
        $viB->update(['diem_kha_dung' => 10]);

        // Moderator xóa tài liệu do vi phạm
        $action = app(DecideDocumentReviewAction::class);
        $action->execute($document->ma_tai_lieu, 'DELETE', 'Tài liệu vi phạm bản quyền.', $this->moderator);

        // Ví SV B bị trừ tối đa về 0, không bị âm (không vi phạm ràng buộc CHECK)
        $this->assertEquals(0, $this->contributor->fresh()->viDiem->diem_kha_dung);

        // SV A vẫn nhận đủ đền bù 50 điểm
        $this->assertEquals(100, $this->requester->fresh()->viDiem->diem_kha_dung);

        // Ghi nhận giao dịch thu hồi đúng 10 điểm từ SV B
        $this->assertDatabaseHas('giao_dich_diem', [
            'ma_vi'             => $viB->ma_vi,
            'loai_giao_dich'    => LoaiGiaoDichDiem::SU_DUNG->value,
            'so_diem'           => 10,
            'thay_doi_kha_dung' => -10,
        ]);
    }

    // 4. Admin duyệt yêu cầu xin gỡ tài liệu từ tác giả gốc (DecideRemovalRequestAction) kích hoạt Clawback
    public function test_admin_approving_copyright_removal_request_triggers_clawback(): void
    {
        Notification::fake();

        [$yeuCau, $dapUng, $document] = $this->setupDisbursedBounty(30);

        // Tạo yêu cầu xin gỡ bỏ tài liệu từ chủ sở hữu
        $removalRequest = YeuCauGoBoTaiLieu::create([
            'ma_tai_lieu'      => $document->ma_tai_lieu,
            'ma_nguoi_yeu_cau' => $this->requester->ma_nguoi_dung,
            'ly_do'            => 'Tôi là tác giả gốc và yêu cầu gỡ tài liệu này.',
            'trang_thai'       => 'CHO_XU_LY',
        ]);

        // Admin duyệt yêu cầu gỡ bỏ
        $action = app(DecideRemovalRequestAction::class);
        $action->execute($removalRequest->getKey(), 'APPROVE', null, $this->admin);

        // Kiểm tra tài liệu đã bị xóa mềm
        $this->assertSoftDeleted('tai_lieu', ['ma_tai_lieu' => $document->ma_tai_lieu]);

        // Kiểm tra thu hồi điểm từ SV B và hoàn tiền cho SV A
        $this->assertEquals(100, $this->contributor->fresh()->viDiem->diem_kha_dung);
        $this->assertEquals(100, $this->requester->fresh()->viDiem->diem_kha_dung);

        // Kiểm tra trạng thái bài nộp đã chuyển sang TU_CHOI
        $this->assertEquals('TU_CHOI', $dapUng->fresh()->trang_thai);
    }
}
