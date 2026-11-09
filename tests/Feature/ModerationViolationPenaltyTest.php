<?php

namespace Tests\Feature;

use App\Enums\TrangThaiDanhGiaMonHoc;
use App\Enums\TrangThaiNguoiDung;
use App\Enums\VaiTroNguoiDung;
use App\Models\BaoCaoDanhGia;
use App\Models\BaoCaoViPham;
use App\Models\DanhGiaMonHoc;
use App\Models\Khoa;
use App\Models\LichSuTrangThaiNguoiDung;
use App\Models\MonHoc;
use App\Models\NguoiDung;
use App\Models\NhatKyKiemToan;
use App\Models\TaiLieu;
use App\Modules\Administration\Domain\Enums\HanhDongKiemToan;
use App\Modules\Moderation\Application\Actions\DecideDocumentReviewAction;
use App\Modules\Moderation\Application\Actions\ResolveReviewReportAction;
use App\Modules\Moderation\Application\Actions\ResolveViolationReportAction;
use App\Modules\Moderation\Infrastructure\Notifications\UserViolationSanctionedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ModerationViolationPenaltyTest extends TestCase
{
    use RefreshDatabase;

    protected NguoiDung $moderator;
    protected NguoiDung $author;
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
            'email'   => 'mod.test@agu.edu.vn',
        ]);

        $this->author = NguoiDung::factory()->create([
            'ma_khoa'        => $khoa->ma_khoa,
            'vai_tro'        => VaiTroNguoiDung::SINH_VIEN,
            'ho_ten'         => 'Nguyễn Văn Tác Giả',
            'email'          => 'author.test@agu.edu.vn',
            'diem_uy_tin'    => 100,
            'so_lan_vi_pham' => 0,
            'muc_canh_bao'   => null,
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
            'ma_nguoi_dang'     => $this->author->ma_nguoi_dung,
            'tieu_de'           => 'Tài liệu kiểm thử',
            'duong_dan_tep'     => 'documents/test.pdf',
            'dinh_dang_tep'     => 'pdf',
            'dung_luong_kb'     => 1024,
            'ma_bam_file'       => hash('sha256', uniqid('', true)),
            'phan_loai'         => 'KHAC',
            'phien_ban'         => '1.0',
            'trang_thai'        => 'DA_DUYET',
            'so_bao_cao_hop_le' => 3,
        ], $attributes));
    }

    // Kiểm tra Moderator giải quyết báo cáo tài liệu bằng hành động DELETE sẽ trừ 20 điểm uy tín và cảnh báo lần 1
    public function test_moderator_deleting_document_report_penalizes_author_with_first_warning(): void
    {
        Notification::fake();

        $document = $this->createDocument([
            'tieu_de'       => 'Tài liệu chứa mã độc',
            'duong_dan_tep' => 'documents/malicious.pdf',
        ]);

        $report = BaoCaoViPham::create([
            'ma_tai_lieu'       => $document->ma_tai_lieu,
            'ma_nguoi_bao_cao'  => $this->moderator->ma_nguoi_dung,
            'ly_do'             => 'MA_DOC',
            'chi_tiet_loi'      => 'Tệp có đính kèm trojan',
            'trang_thai_xu_ly'  => 'CHO_XU_LY',
        ]);

        $action = app(ResolveViolationReportAction::class);
        $result = $action->execute($report->ma_bao_cao, 'DELETE', 'Nội dung chứa tệp độc hại nguy hiểm.', $this->moderator);

        $this->assertSame('xóa mềm', $result);

        // Kiểm tra tác giả bị trừ đúng 20 điểm và nhận cảnh báo
        $this->author->refresh();
        $this->assertSame(80, $this->author->diem_uy_tin);
        $this->assertSame(1, $this->author->so_lan_vi_pham);
        $this->assertSame('CANH_BAO', $this->author->muc_canh_bao);
        $this->assertSame(TrangThaiNguoiDung::DANG_HOAT_DONG, $this->author->trang_thai);

        // Kiểm tra tài liệu đã bị xóa mềm
        $this->assertSoftDeleted('tai_lieu', ['ma_tai_lieu' => $document->ma_tai_lieu]);

        // Kiểm tra thông báo chế tài được gửi
        Notification::assertSentTo($this->author, UserViolationSanctionedNotification::class, function ($notification) {
            $data = $notification->toDatabase($this->author)->data;
            return $data['penalty_points'] === 20
                && $data['new_reputation'] === 80
                && $data['so_lan_vi_pham'] === 1
                && $data['muc_canh_bao'] === 'CANH_BAO'
                && $data['is_locked'] === false;
        });

        // Kiểm tra nhật ký kiểm toán ghi nhận vi phạm
        $this->assertDatabaseHas('nhat_ky_kiem_toan', [
            'ma_nguoi_thuc_hien' => $this->moderator->ma_nguoi_dung,
            'hanh_dong'          => HanhDongKiemToan::GHI_NHAN_VI_PHAM->value,
            'ma_doi_tuong'       => (string) $this->author->ma_nguoi_dung,
        ]);
    }

    // Kiểm tra vi phạm lần thứ 2 sẽ tự động tạm khóa tài khoản tác giả và ghi nhận lịch sử trạng thái
    public function test_second_violation_automatically_locks_user_account(): void
    {
        Notification::fake();

        // Đặt tài khoản tác giả đã vi phạm 1 lần trước đó
        $this->author->update([
            'so_lan_vi_pham' => 1,
            'diem_uy_tin'    => 80,
            'muc_canh_bao'   => 'CANH_BAO',
        ]);

        $document = $this->createDocument([
            'tieu_de'       => 'Tài liệu vi phạm bản quyền tái phạm',
            'duong_dan_tep' => 'documents/copyright.pdf',
            'dung_luong_kb' => 2048,
            'trang_thai'    => 'CHO_KIEM_TRA',
        ]);

        $action = app(DecideDocumentReviewAction::class);
        $action->execute($document->ma_tai_lieu, 'DELETE', 'Tái phạm phát tán tài liệu vi phạm bản quyền nghiêm trọng.', $this->moderator);

        // Kiểm tra tác giả bị trừ điểm tiếp và tự động bị tạm khóa
        $this->author->refresh();
        $this->assertSame(60, $this->author->diem_uy_tin);
        $this->assertSame(2, $this->author->so_lan_vi_pham);
        $this->assertSame('TAM_KHOA', $this->author->muc_canh_bao);
        $this->assertSame(TrangThaiNguoiDung::TAM_KHOA, $this->author->trang_thai);
        $this->assertSame('Tái phạm phát tán tài liệu vi phạm bản quyền nghiêm trọng.', $this->author->ly_do_khoa);

        // Kiểm tra bản ghi lịch sử trạng thái người dùng
        $this->assertDatabaseHas('lich_su_trang_thai_nguoi_dung', [
            'ma_nguoi_dung'      => $this->author->ma_nguoi_dung,
            'ma_nguoi_thuc_hien' => $this->moderator->ma_nguoi_dung,
            'trang_thai_moi'     => TrangThaiNguoiDung::TAM_KHOA->value,
            'hanh_dong'          => 'TAM_KHOA',
        ]);

        // Kiểm tra thông báo tạm khóa được gửi
        Notification::assertSentTo($this->author, UserViolationSanctionedNotification::class, function ($notification) {
            $data = $notification->toDatabase($this->author)->data;
            return $data['is_locked'] === true
                && $data['muc_canh_bao'] === 'TAM_KHOA'
                && $data['so_lan_vi_pham'] === 2;
        });
    }

    // Kiểm tra Moderator xóa bài đánh giá môn học vi phạm sẽ trừ 10 điểm uy tín
    public function test_moderator_deleting_review_penalizes_author_with_review_penalty_points(): void
    {
        Notification::fake();

        $review = DanhGiaMonHoc::create([
            'ma_mon_hoc'      => $this->course->ma_mon_hoc,
            'ma_nguoi_dung'   => $this->author->ma_nguoi_dung,
            'hoc_ky'          => '2025-1',
            'diem_do_kho'     => 1,
            'diem_thuc_tien'  => 1,
            'diem_khoi_luong' => 1,
            'noi_dung'        => 'Nội dung bôi nhọ giảng viên tục tĩu.',
            'an_danh'         => false,
            'trang_thai'      => TrangThaiDanhGiaMonHoc::DA_DUYET->value,
        ]);

        $report = BaoCaoDanhGia::create([
            'ma_danh_gia'      => $review->ma_danh_gia,
            'ma_nguoi_bao_cao' => $this->moderator->ma_nguoi_dung,
            'ly_do'            => 'NGON_TU_KICH_DONG',
            'chi_tiet'         => 'Sử dụng từ ngữ xúc phạm nặng nề',
            'trang_thai'       => 'CHO_XU_LY',
        ]);

        $action = app(ResolveReviewReportAction::class);
        $result = $action->execute($report->ma_bao_cao, 'DELETE', 'Vi phạm quy tắc ứng xử văn hóa học đường.', $this->moderator);

        $this->assertSame('gỡ bỏ khỏi hệ thống', $result);

        // Kiểm tra tác giả bài đánh giá bị trừ 10 điểm
        $this->author->refresh();
        $this->assertSame(90, $this->author->diem_uy_tin);
        $this->assertSame(1, $this->author->so_lan_vi_pham);
        $this->assertSame('CANH_BAO', $this->author->muc_canh_bao);

        $this->assertSoftDeleted('danh_gia_mon_hoc', ['ma_danh_gia' => $review->ma_danh_gia]);
    }

    // Kiểm tra điểm uy tín không bị giảm xuống dưới 0
    public function test_reputation_points_cannot_drop_below_zero(): void
    {
        $this->author->update(['diem_uy_tin' => 10]);

        $document = $this->createDocument([
            'tieu_de'       => 'Tài liệu vi phạm khi điểm thấp',
            'duong_dan_tep' => 'documents/low_score.pdf',
            'dung_luong_kb' => 512,
            'trang_thai'    => 'CHO_KIEM_TRA',
        ]);

        $action = app(DecideDocumentReviewAction::class);
        $action->execute($document->ma_tai_lieu, 'DELETE', 'Xóa tài liệu vi phạm.', $this->moderator);

        $this->author->refresh();
        // 10 - 20 = -10 nhưng sàn là 0
        $this->assertSame(0, $this->author->diem_uy_tin);
    }
}
