<?php

namespace Tests\Feature;

use App\Enums\TrangThaiDanhGiaMonHoc;
use App\Enums\VaiTroNguoiDung;
use App\Models\BaoCaoDanhGia;
use App\Models\BaoCaoViPham;
use App\Models\DanhGiaMonHoc;
use App\Models\Khoa;
use App\Models\MonHoc;
use App\Models\NguoiDung;
use App\Models\NhatKyKiemToan;
use App\Models\TaiLieu;
use App\Modules\Administration\Domain\Enums\HanhDongKiemToan;
use App\Modules\Moderation\Infrastructure\Notifications\NewReviewViolationReportNotification;
use App\Modules\Moderation\Infrastructure\Notifications\ReviewReportResolvedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ReviewModerationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected NguoiDung $student;
    protected NguoiDung $reporter;
    protected NguoiDung $moderator;
    protected NguoiDung $admin;
    protected MonHoc $course;
    protected DanhGiaMonHoc $review;

    protected function setUp(): void
    {
        parent::setUp();

        $khoa = Khoa::create([
            'ten_khoa'      => 'Khoa Công nghệ Thông tin',
            'ky_hieu_khoa'  => 'CNTT',
            'trang_thai'    => true,
        ]);

        $this->student = NguoiDung::factory()->create([
            'ma_khoa'    => $khoa->ma_khoa,
            'vai_tro'    => VaiTroNguoiDung::SINH_VIEN,
            'ho_ten'     => 'Nguyễn Văn Tác Giả',
            'email'      => 'author@agu.edu.vn',
            'mssv'       => 'DTH210001',
        ]);

        $this->reporter = NguoiDung::factory()->create([
            'ma_khoa'    => $khoa->ma_khoa,
            'vai_tro'    => VaiTroNguoiDung::SINH_VIEN,
            'ho_ten'     => 'Trần Thị Người Báo Cáo',
            'email'      => 'reporter@agu.edu.vn',
            'mssv'       => 'DTH210002',
        ]);

        $this->moderator = NguoiDung::factory()->create([
            'ma_khoa'    => $khoa->ma_khoa,
            'vai_tro'    => VaiTroNguoiDung::KIEM_DUYET_VIEN,
            'ho_ten'     => 'Kiểm Duyệt Viên Hub',
            'email'      => 'moderator@agu.edu.vn',
        ]);

        $this->admin = NguoiDung::factory()->create([
            'ma_khoa'    => $khoa->ma_khoa,
            'vai_tro'    => VaiTroNguoiDung::QUAN_TRI_VIEN,
            'ho_ten'     => 'Quản Trị Viên Hub',
            'email'      => 'admin@agu.edu.vn',
        ]);

        $this->course = MonHoc::create([
            'ma_khoa'     => $khoa->ma_khoa,
            'ma_hoc_phan' => 'COS201',
            'ten_mon_hoc' => 'Cấu trúc dữ liệu và giải thuật',
            'so_tin_chi'  => 4,
            'mo_ta'       => 'Môn học trọng tâm chuyên ngành CNTT',
            'trang_thai'  => true,
        ]);

        $this->review = DanhGiaMonHoc::create([
            'ma_mon_hoc'          => $this->course->ma_mon_hoc,
            'ma_nguoi_dung'       => $this->student->ma_nguoi_dung,
            'hoc_ky'              => '2025-1',
            'diem_do_kho'         => 4,
            'diem_thuc_tien'      => 4,
            'diem_khoi_luong'     => 5,
            'noi_dung'            => 'Môn này thi quá gắt, giảng viên dạy chán.',
            'an_danh'             => false,
            'trang_thai'          => TrangThaiDanhGiaMonHoc::DA_DUYET->value,
        ]);
    }

    public function test_guest_and_student_cannot_access_review_reports_queue(): void
    {
        $this->get(route('admin.review-reports.index'))
            ->assertRedirect(route('login'));

        $this->actingAs($this->student)
            ->get(route('admin.review-reports.index'))
            ->assertForbidden();
    }

    public function test_admin_and_moderator_can_view_review_reports_queue(): void
    {
        BaoCaoDanhGia::create([
            'ma_danh_gia'      => $this->review->ma_danh_gia,
            'ma_nguoi_bao_cao' => $this->reporter->ma_nguoi_dung,
            'ly_do'            => 'BOI_NHO',
            'chi_tiet'         => 'Nội dung nhận xét công kích thiếu căn cứ.',
            'trang_thai'       => 'CHO_XU_LY',
        ]);

        $this->actingAs($this->moderator)
            ->get(route('admin.review-reports.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/ReviewReports/Index')
                ->has('reports.data', 1)
                ->has('stats')
            );

        $this->actingAs($this->admin)
            ->get(route('admin.review-reports.index'))
            ->assertOk();
    }

    public function test_moderator_can_view_report_detail(): void
    {
        $report = BaoCaoDanhGia::create([
            'ma_danh_gia'      => $this->review->ma_danh_gia,
            'ma_nguoi_bao_cao' => $this->reporter->ma_nguoi_dung,
            'ly_do'            => 'NGON_TU_KICH_DONG',
            'chi_tiet'         => 'Ngôn từ xúc phạm giảng viên.',
            'trang_thai'       => 'CHO_XU_LY',
        ]);

        $this->actingAs($this->moderator)
            ->get(route('admin.review-reports.show', $report->ma_bao_cao))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/ReviewReports/Show')
                ->where('report.ma_bao_cao', $report->ma_bao_cao)
            );
    }

    public function test_moderator_can_resolve_report_with_hide(): void
    {
        Notification::fake();

        $report = BaoCaoDanhGia::create([
            'ma_danh_gia'      => $this->review->ma_danh_gia,
            'ma_nguoi_bao_cao' => $this->reporter->ma_nguoi_dung,
            'ly_do'            => 'BOI_NHO',
            'chi_tiet'         => 'Bôi nhọ uy tín.',
            'trang_thai'       => 'CHO_XU_LY',
        ]);

        $response = $this->actingAs($this->moderator)
            ->put(route('admin.review-reports.resolve', $report->ma_bao_cao), [
                'action'            => 'HIDE',
                'resolution_reason' => 'Tạm ẩn để xác minh thông tin với khoa.',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $report->refresh();
        $this->assertSame('DA_XU_LY', $report->trang_thai);
        $this->assertSame($this->moderator->ma_nguoi_dung, $report->nguoi_xu_ly);
        $this->assertSame('Tạm ẩn để xác minh thông tin với khoa.', $report->ghi_chu_xu_ly);
        $this->assertNotNull($report->thoi_gian_xu_ly);

        $this->review->refresh();
        $this->assertSame(TrangThaiDanhGiaMonHoc::TAM_AN, $this->review->trang_thai);

        // Kiểm tra Audit Log
        $this->assertDatabaseHas('nhat_ky_kiem_toan', [
            'hanh_dong' => HanhDongKiemToan::XU_LY_BAO_CAO_DANH_GIA->value,
            'ma_doi_tuong' => (string) $report->ma_bao_cao,
        ]);

        // Kiểm tra Notification
        Notification::assertSentTo(
            $this->reporter,
            ReviewReportResolvedNotification::class
        );
        Notification::assertSentTo(
            $this->student,
            ReviewReportResolvedNotification::class
        );
    }

    public function test_moderator_can_resolve_report_with_delete(): void
    {
        Notification::fake();

        $report = BaoCaoDanhGia::create([
            'ma_danh_gia'      => $this->review->ma_danh_gia,
            'ma_nguoi_bao_cao' => $this->reporter->ma_nguoi_dung,
            'ly_do'            => 'NGON_TU_KICH_DONG',
            'chi_tiet'         => 'Ngôn từ xúc phạm nghiêm trọng.',
            'trang_thai'       => 'CHO_XU_LY',
        ]);

        $response = $this->actingAs($this->moderator)
            ->put(route('admin.review-reports.resolve', $report->ma_bao_cao), [
                'action'            => 'DELETE',
                'resolution_reason' => 'Vi phạm nghiêm trọng tiêu chuẩn văn hóa học đường.',
            ]);

        $response->assertRedirect();

        $report->refresh();
        $this->assertSame('DA_XU_LY', $report->trang_thai);

        // Bài đánh giá bị xóa mềm
        $this->assertSoftDeleted('danh_gia_mon_hoc', [
            'ma_danh_gia' => $this->review->ma_danh_gia,
            'trang_thai'  => TrangThaiDanhGiaMonHoc::TU_CHOI->value,
        ]);
    }

    public function test_moderator_can_resolve_report_with_dismiss(): void
    {
        $report = BaoCaoDanhGia::create([
            'ma_danh_gia'      => $this->review->ma_danh_gia,
            'ma_nguoi_bao_cao' => $this->reporter->ma_nguoi_dung,
            'ly_do'            => 'SPAM',
            'chi_tiet'         => 'Báo cáo sai sự thật.',
            'trang_thai'       => 'CHO_XU_LY',
        ]);

        $response = $this->actingAs($this->moderator)
            ->put(route('admin.review-reports.resolve', $report->ma_bao_cao), [
                'action'            => 'DISMISS',
                'resolution_reason' => 'Nhận xét mang tính đóng góp xây dựng, không vi phạm.',
            ]);

        $response->assertRedirect();

        $report->refresh();
        $this->assertSame('BAC_BO', $report->trang_thai);

        $this->review->refresh();
        $this->assertSame(TrangThaiDanhGiaMonHoc::DA_DUYET, $this->review->trang_thai);
    }

    public function test_moderator_can_archive_resolved_report(): void
    {
        $report = BaoCaoDanhGia::create([
            'ma_danh_gia'      => $this->review->ma_danh_gia,
            'ma_nguoi_bao_cao' => $this->reporter->ma_nguoi_dung,
            'ly_do'            => 'BOI_NHO',
            'trang_thai'       => 'DA_XU_LY',
        ]);

        $response = $this->actingAs($this->moderator)
            ->delete(route('admin.review-reports.archive', $report->ma_bao_cao));

        $response->assertRedirect();
        $this->assertSoftDeleted('bao_cao_danh_gia', [
            'ma_bao_cao' => $report->ma_bao_cao,
        ]);
    }

    public function test_student_can_report_review_and_staff_notified(): void
    {
        Notification::fake();

        $response = $this->actingAs($this->reporter)
            ->post(route('reviews.report', $this->review->ma_danh_gia), [
                'ly_do'    => 'NGON_TU_KICH_DONG',
                'chi_tiet' => 'Từ ngữ tiêu cực quá mức.',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('bao_cao_danh_gia', [
            'ma_danh_gia'      => $this->review->ma_danh_gia,
            'ma_nguoi_bao_cao' => $this->reporter->ma_nguoi_dung,
            'ly_do'            => 'NGON_TU_KICH_DONG',
            'trang_thai'       => 'CHO_XU_LY',
        ]);

        Notification::assertSentTo(
            [$this->moderator, $this->admin],
            NewReviewViolationReportNotification::class
        );
    }

    public function test_moderator_can_view_and_filter_unified_reports_by_type(): void
    {
        // 1. Tạo báo cáo Môn học
        BaoCaoDanhGia::create([
            'ma_danh_gia'      => $this->review->ma_danh_gia,
            'loai_doi_tuong'   => 'MON_HOC',
            'ma_nguoi_bao_cao' => $this->reporter->ma_nguoi_dung,
            'ly_do'            => 'SPAM',
            'chi_tiet'         => 'Nội dung quảng cáo đồ án.',
            'trang_thai'       => 'CHO_XU_LY',
        ]);

        // 2. Tạo báo cáo Giảng viên
        BaoCaoDanhGia::create([
            'ma_danh_gia'      => $this->review->ma_danh_gia,
            'loai_doi_tuong'   => 'GIANG_VIEN',
            'ten_giang_vien'   => 'TS. Lê Hoàng Nam',
            'ma_nguoi_bao_cao' => $this->student->ma_nguoi_dung,
            'ly_do'            => 'BOI_NHO',
            'chi_tiet'         => 'Bôi nhọ danh dự thầy giáo.',
            'trang_thai'       => 'CHO_XU_LY',
        ]);

        // 3. Tạo báo cáo Tài liệu
        $document = TaiLieu::factory()->create([
            'ma_mon_hoc'    => $this->course->ma_mon_hoc,
            'ma_nguoi_dang' => $this->student->ma_nguoi_dung,
            'tieu_de'       => 'Đề thi cuối kỳ CNTT',
        ]);

        BaoCaoViPham::create([
            'ma_tai_lieu'      => $document->ma_tai_lieu,
            'ma_nguoi_bao_cao' => $this->reporter->ma_nguoi_dung,
            'ly_do'            => 'BAN_QUYEN',
            'chi_tiet_loi'     => 'Tài liệu độc quyền của trường.',
            'trang_thai_xu_ly' => 'CHO_XU_LY',
        ]);

        // Kiểm tra Hàng đợi thống nhất hiển thị cả 3 báo cáo
        $this->actingAs($this->moderator)
            ->get(route('admin.review-reports.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/ReviewReports/Index')
                ->has('reports.data', 3)
                ->where('stats.course_count', 1)
                ->where('stats.lecturer_count', 1)
                ->where('stats.document_count', 1)
                ->where('stats.total', 3)
            );

        // Lọc theo Môn học
        $this->actingAs($this->moderator)
            ->get(route('admin.review-reports.index', ['type' => 'MON_HOC']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('reports.data', 1)
                ->where('reports.data.0.loai_doi_tuong', 'MON_HOC')
            );

        // Lọc theo Giảng viên
        $this->actingAs($this->moderator)
            ->get(route('admin.review-reports.index', ['type' => 'GIANG_VIEN']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('reports.data', 1)
                ->where('reports.data.0.loai_doi_tuong', 'GIANG_VIEN')
                ->where('reports.data.0.ten_giang_vien', 'TS. Lê Hoàng Nam')
            );

        // Lọc theo Tài liệu
        $this->actingAs($this->moderator)
            ->get(route('admin.review-reports.index', ['type' => 'TAI_LIEU']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('reports.data', 1)
                ->where('reports.data.0.loai_doi_tuong', 'TAI_LIEU')
            );
    }

    public function test_student_can_report_review_targeting_lecturer_with_name(): void
    {
        Notification::fake();

        $response = $this->actingAs($this->reporter)
            ->post(route('reviews.report', $this->review->ma_danh_gia), [
                'ly_do'          => 'BOI_NHO',
                'loai_doi_tuong' => 'GIANG_VIEN',
                'ten_giang_vien' => 'TS. Lê Hoàng Nam',
                'chi_tiet'       => 'Vu khống chấm điểm thiên vị.',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('bao_cao_danh_gia', [
            'ma_danh_gia'      => $this->review->ma_danh_gia,
            'ma_nguoi_bao_cao' => $this->reporter->ma_nguoi_dung,
            'loai_doi_tuong'   => 'GIANG_VIEN',
            'ten_giang_vien'   => 'TS. Lê Hoàng Nam',
            'ly_do'            => 'BOI_NHO',
            'trang_thai'       => 'CHO_XU_LY',
        ]);
    }
}

