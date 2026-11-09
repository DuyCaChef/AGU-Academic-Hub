<?php

namespace Tests\Feature;

use App\Enums\TrangThaiNguoiDung;
use App\Enums\TrangThaiYeuCauTaiLieu;
use App\Enums\VaiTroNguoiDung;
use App\Models\BaoCaoViPham;
use App\Models\DapUngYeuCauTaiLieu;
use App\Models\Khoa;
use App\Models\MonHoc;
use App\Models\NguoiDung;
use App\Models\TaiLieu;
use App\Models\YeuCauTaiLieu;
use App\Modules\Moderation\Application\Actions\DecideDocumentReviewAction;
use App\Modules\Moderation\Application\Actions\ResolveViolationReportAction;
use App\Modules\Moderation\Infrastructure\Notifications\BountySubmissionCancelledNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ModerationBountySubmissionSyncTest extends TestCase
{
    use RefreshDatabase;

    protected NguoiDung $moderator;
    protected NguoiDung $requester;
    protected NguoiDung $contributor;
    protected MonHoc $course;

    protected function setUp(): void
    {
        parent::setUp();

        $khoa = Khoa::create([
            'ten_khoa'     => 'Khoa Công nghệ Thông tin',
            'ky_hieu_khoa' => 'CNTT',
            'trang_thai'   => true,
        ]);

        $this->moderator = NguoiDung::factory()->create([
            'ma_khoa' => $khoa->ma_khoa,
            'vai_tro' => VaiTroNguoiDung::KIEM_DUYET_VIEN,
            'ho_ten'  => 'Kiểm Duyệt Viên AGU',
            'email'   => 'mod.sync@agu.edu.vn',
        ]);

        $this->requester = NguoiDung::factory()->create([
            'ma_khoa'        => $khoa->ma_khoa,
            'vai_tro'        => VaiTroNguoiDung::SINH_VIEN,
            'ho_ten'         => 'Sinh Viên A (Người yêu cầu)',
            'email'          => 'studentA@agu.edu.vn',
            'diem_uy_tin'    => 100,
            'trang_thai'     => TrangThaiNguoiDung::DANG_HOAT_DONG,
        ]);

        $this->contributor = NguoiDung::factory()->create([
            'ma_khoa'        => $khoa->ma_khoa,
            'vai_tro'        => VaiTroNguoiDung::SINH_VIEN,
            'ho_ten'         => 'Sinh Viên B (Người đóng góp)',
            'email'          => 'studentB@agu.edu.vn',
            'diem_uy_tin'    => 100,
            'so_lan_vi_pham' => 0,
            'trang_thai'     => TrangThaiNguoiDung::DANG_HOAT_DONG,
        ]);

        $this->course = MonHoc::create([
            'ma_khoa'     => $khoa->ma_khoa,
            'ma_hoc_phan' => 'COS202',
            'ten_mon_hoc' => 'Lập trình Hướng đối tượng',
            'so_tin_chi'  => 3,
            'mo_ta'       => 'Môn học chuyên ngành',
            'trang_thai'  => true,
        ]);
    }

    private function createDocument(array $attributes = []): TaiLieu
    {
        return TaiLieu::create(array_merge([
            'ma_mon_hoc'        => $this->course->ma_mon_hoc,
            'ma_nguoi_dang'     => $this->contributor->ma_nguoi_dung,
            'tieu_de'           => 'Tài liệu giải bài tập OOP',
            'duong_dan_tep'     => 'documents/oop_solutions.pdf',
            'dinh_dang_tep'     => 'pdf',
            'dung_luong_kb'     => 1024,
            'ma_bam_file'       => hash('sha256', uniqid('', true)),
            'phan_loai'         => 'BAI_TAP',
            'phien_ban'         => '1.0',
            'trang_thai'        => 'CHO_KIEM_TRA',
            'so_bao_cao_hop_le' => 3,
        ], $attributes));
    }

    // Kiểm tra Moderator xóa mềm tài liệu trong hàng đợi kiểm tra tự động cập nhật bài nộp Bounty sang TU_CHOI
    public function test_deleting_document_in_review_queue_automatically_rejects_linked_bounty_submission(): void
    {
        Notification::fake();

        // 1. Tạo yêu cầu Bounty từ SV A
        $yeuCau = YeuCauTaiLieu::create([
            'ma_mon_hoc'   => $this->course->ma_mon_hoc,
            'ma_nguoi_tao' => $this->requester->ma_nguoi_dung,
            'tieu_de'      => 'Cần tìm lời giải bài tập OOP tuần 5',
            'phan_loai'    => 'BAI_TAP',
            'diem_thuong'  => 50,
            'trang_thai'   => TrangThaiYeuCauTaiLieu::MO,
        ]);

        // 2. SV B đóng góp tài liệu cho yêu cầu này
        $document = $this->createDocument([
            'tieu_de'    => 'Lời giải bài tập OOP tuần 5',
            'trang_thai' => 'CHO_KIEM_TRA',
        ]);

        $dapUng = DapUngYeuCauTaiLieu::create([
            'ma_yeu_cau'        => $yeuCau->ma_yeu_cau,
            'ma_tai_lieu'       => $document->ma_tai_lieu,
            'ma_nguoi_dong_gop' => $this->contributor->ma_nguoi_dung,
            'trang_thai'        => 'CHO_DUYET',
            'ghi_chu_dong_gop'  => 'Đây là bài giải chi tiết đính kèm.',
        ]);

        // 3. Moderator kiểm tra hàng đợi và quyết định XÓA MỀM (DELETE) do tài liệu vi phạm
        $action = app(DecideDocumentReviewAction::class);
        $action->execute($document->ma_tai_lieu, 'DELETE', 'Tài liệu vi phạm bản quyền bài thi nội bộ.', $this->moderator);

        // 4. Kiểm tra tài liệu đã bị xóa mềm
        $this->assertSoftDeleted('tai_lieu', ['ma_tai_lieu' => $document->ma_tai_lieu]);

        // 5. Kiểm tra bài nộp Bounty tự động chuyển sang TU_CHOI kèm lý do chuẩn
        $dapUng->refresh();
        $this->assertSame('TU_CHOI', $dapUng->trang_thai);
        $this->assertSame('Tài liệu đính kèm đã bị Ban kiểm duyệt gỡ bỏ do vi phạm quy định.', $dapUng->ly_do_tu_choi_giai_ngan);
        $this->assertSame($this->moderator->ma_nguoi_dung, $dapUng->nguoi_duyet);
        $this->assertNotNull($dapUng->thoi_gian_duyet);

        // 6. Kiểm tra SV A (người tạo yêu cầu) nhận được thông báo bài nộp bị từ chối và yêu cầu được mở lại
        Notification::assertSentTo($this->requester, BountySubmissionCancelledNotification::class, function ($notification) use ($yeuCau) {
            $data = $notification->toDatabase($this->requester)->data;
            return $data['ma_yeu_cau'] === $yeuCau->ma_yeu_cau
                && str_contains($data['message'], 'bị Ban kiểm duyệt gỡ bỏ');
        });
    }

    // Kiểm tra xử lý báo cáo vi phạm với DELETE tự động từ chối bài nộp và mở lại yêu cầu nếu đang chờ giải ngân
    public function test_resolving_violation_report_with_delete_rejects_bounty_submission_and_reopens_request(): void
    {
        Notification::fake();

        // 1. Tạo yêu cầu Bounty từ SV A
        $yeuCau = YeuCauTaiLieu::create([
            'ma_mon_hoc'   => $this->course->ma_mon_hoc,
            'ma_nguoi_tao' => $this->requester->ma_nguoi_dung,
            'tieu_de'      => 'Tìm tài liệu trắc nghiệm OOP',
            'phan_loai'    => 'DE_THI',
            'diem_thuong'  => 100,
            'trang_thai'   => TrangThaiYeuCauTaiLieu::CHO_GIAI_NGAN,
        ]);

        $document = $this->createDocument([
            'tieu_de'    => 'Đề thi trắc nghiệm OOP có đáp án',
            'trang_thai' => 'DA_DUYET',
        ]);

        // 2. Bài nộp được SV A xác nhận và đang chờ Admin duyệt giải ngân
        $dapUng = DapUngYeuCauTaiLieu::create([
            'ma_yeu_cau'        => $yeuCau->ma_yeu_cau,
            'ma_tai_lieu'       => $document->ma_tai_lieu,
            'ma_nguoi_dong_gop' => $this->contributor->ma_nguoi_dung,
            'trang_thai'        => 'NGUOI_YEU_CAU_XAC_NHAN',
            'ghi_chu_dong_gop'  => 'Đề thi chuẩn.',
        ]);

        $yeuCau->update([
            'ma_dap_ung_duoc_chon'             => $dapUng->ma_dap_ung,
            'thoi_gian_nguoi_yeu_cau_xac_nhan' => now(),
        ]);

        // 3. Có người báo cáo tài liệu này chứa mã độc
        $report = BaoCaoViPham::create([
            'ma_tai_lieu'       => $document->ma_tai_lieu,
            'ma_nguoi_bao_cao'  => $this->moderator->ma_nguoi_dung,
            'ly_do'             => 'MA_DOC',
            'chi_tiet_loi'      => 'File pdf có kèm mã độc macro',
            'trang_thai_xu_ly'  => 'CHO_XU_LY',
        ]);

        // 4. Moderator giải quyết báo cáo bằng DELETE
        $action = app(ResolveViolationReportAction::class);
        $action->execute($report->ma_bao_cao, 'DELETE', 'Xác nhận tệp có mã độc nguy hiểm.', $this->moderator);

        // 5. Kiểm tra bài nộp bị chuyển sang TU_CHOI
        $dapUng->refresh();
        $this->assertSame('TU_CHOI', $dapUng->trang_thai);

        // 6. Kiểm tra yêu cầu Bounty được mở lại sang MO và hủy chọn bài nộp bị xóa
        $yeuCau->refresh();
        $this->assertSame(TrangThaiYeuCauTaiLieu::MO, $yeuCau->trang_thai);
        $this->assertNull($yeuCau->ma_dap_ung_duoc_chon);
        $this->assertNull($yeuCau->thoi_gian_nguoi_yeu_cau_xac_nhan);

        // 7. Kiểm tra SV B bị trừ điểm uy tín vì vi phạm
        $this->contributor->refresh();
        $this->assertSame(80, $this->contributor->diem_uy_tin);
        $this->assertSame(1, $this->contributor->so_lan_vi_pham);
    }
}

