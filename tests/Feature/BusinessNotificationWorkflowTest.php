<?php

namespace Tests\Feature;

use App\Enums\LoaiXacThuc;
use App\Enums\TrangThaiNguoiDung;
use App\Enums\TrangThaiXacThuc;
use App\Enums\VaiTroNguoiDung;
use App\Models\BaoCaoViPham;
use App\Models\DanhSachSinhVienRaw;
use App\Models\Khoa;
use App\Models\MonHoc;
use App\Models\NguoiDung;
use App\Models\TaiLieu;
use App\Models\ThongBao;
use App\Models\YeuCauGoBoTaiLieu;
use App\Models\YeuCauXacThuc;
use App\Modules\Administration\Application\Actions\ImportRawStudentsAction;
use App\Modules\Administration\Infrastructure\Notifications\StudentRosterImportedNotification;
use App\Modules\Administration\Infrastructure\Notifications\StudentRosterImportWarningNotification;
use App\Modules\Documents\Application\Actions\CheckExpiringTrashDocumentsAction;
use App\Modules\Documents\Infrastructure\Notifications\DocumentsExpiringInTrashNotification;
use App\Modules\Identity\Application\Actions\ApproveKycAction;
use App\Modules\Identity\Application\Actions\ChangeFirstTimePasswordAction;
use App\Modules\Identity\Application\Actions\RejectKycAction;
use App\Modules\Identity\Application\Actions\SubmitAlumniKycAction;
use App\Modules\Identity\Infrastructure\Notifications\AlumniKycReceivedNotification;
use App\Modules\Identity\Infrastructure\Notifications\FirstTimePasswordChangeRequiredNotification;
use App\Modules\Identity\Infrastructure\Notifications\NewAlumniKycNotification;
use App\Modules\Documents\Infrastructure\Notifications\DocumentRemovalRequestSubmittedNotification;
use App\Modules\Identity\Infrastructure\Notifications\AlumniKycDecisionNotification;
use App\Modules\Identity\Infrastructure\Notifications\StudentAccountActivatedNotification;
use App\Modules\Moderation\Domain\Services\DocumentReportRecalculator;
use App\Modules\Moderation\Infrastructure\Notifications\DocumentReviewRequiredNotification;
use App\Modules\Moderation\Infrastructure\Notifications\NewViolationReportNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BusinessNotificationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function createDocument(?NguoiDung $uploader = null): TaiLieu
    {
        $user = $uploader ?? NguoiDung::factory()->create();
        $course = MonHoc::factory()->create();

        return TaiLieu::create([
            'ma_mon_hoc' => $course->ma_mon_hoc,
            'ma_nguoi_dang' => $user->ma_nguoi_dung,
            'tieu_de' => 'Giao trinh Toan Roi Rac',
            'mo_ta' => 'Tai lieu chuan cho sinh vien',
            'nam_hoc' => 2026,
            'hoc_ky' => 1,
            'phan_loai' => 'GIAO_TRINH',
            'dinh_dang_tep' => 'pdf',
            'dung_luong_kb' => 1024,
            'ma_bam_file' => hash('sha256', uniqid('', true)),
            'duong_dan_tep' => 'documents/sample.pdf',
            'trang_thai' => 'DA_DUYET',
            'luot_tai' => 0,
            'so_bao_cao_hop_le' => 0,
        ]);
    }

    // =========================================================================
    // 1. CÁC LUỒNG THÔNG BÁO CHO ADMIN / MODERATOR
    // =========================================================================

    public function test_admin_and_moderator_receive_notification_on_new_alumni_kyc(): void
    {
        Notification::fake();

        $admin = NguoiDung::factory()->admin()->create();
        $mod = NguoiDung::factory()->moderator()->create();
        $student = NguoiDung::factory()->create(['vai_tro' => VaiTroNguoiDung::SINH_VIEN]);
        $faculty = Khoa::factory()->create();

        $action = app(SubmitAlumniKycAction::class);
        $file = UploadedFile::fake()->image('diploma.jpg');

        $user = $action->execute([
            'ho_ten' => 'Nguyen Van Alumni',
            'email' => 'alumni.new@example.com',
            'mssv' => 'DTH181001',
            'nam_tot_nghiep' => 2022,
            'ma_khoa' => $faculty->ma_khoa,
            'mat_khau' => 'Password@123',
            'loai_minh_chung' => 'BANG_TOT_NGHIEP',
        ], $file);

        // Admin & Moderator nhận thông báo có hồ sơ KYC mới
        Notification::assertSentTo($admin, NewAlumniKycNotification::class, function ($n) {
            $data = $n->toDatabase(new NguoiDung());
            return $data->data['title'] === 'Có hồ sơ Alumni KYC mới' &&
                str_contains($data->data['message'], 'Nguyen Van Alumni');
        });

        Notification::assertSentTo($mod, NewAlumniKycNotification::class);

        // Sinh viên thường KHÔNG nhận thông báo KYC
        Notification::assertNotSentTo($student, NewAlumniKycNotification::class);

        // Cựu sinh viên nhận thông báo đã tiếp nhận và đang chờ xử lý
        Notification::assertSentTo($user, AlumniKycReceivedNotification::class, function ($n) {
            $data = $n->toDatabase(new NguoiDung());
            return $data->data['title'] === 'Đã tiếp nhận hồ sơ KYC' &&
                str_contains($data->data['message'], 'đang chờ xử lý');
        });
    }

    public function test_admin_and_moderator_receive_notification_on_new_violation_report(): void
    {
        Notification::fake();

        $admin = NguoiDung::factory()->admin()->create();
        $mod = NguoiDung::factory()->moderator()->create();
        $reporter = NguoiDung::factory()->create(['diem_uy_tin' => 100]);
        $doc = $this->createDocument();

        $recalculator = app(DocumentReportRecalculator::class);
        $recalculator->createReport($doc, $reporter, [
            'ly_do' => 'SAI_NOI_DUNG',
            'chi_tiet_loi' => 'Tai lieu sai lech kien thuc.',
        ]);

        Notification::assertSentTo($admin, NewViolationReportNotification::class, function ($n) use ($doc) {
            $data = $n->toDatabase(new NguoiDung());
            return $data->data['title'] === 'Có báo cáo vi phạm mới' &&
                str_contains($data->data['message'], $doc->tieu_de);
        });

        Notification::assertSentTo($mod, NewViolationReportNotification::class);
    }

    public function test_admin_and_moderator_notified_when_document_reaches_review_threshold(): void
    {
        Notification::fake();

        $admin = NguoiDung::factory()->admin()->create();
        $doc = $this->createDocument();
        $recalculator = app(DocumentReportRecalculator::class);

        // Gửi 3 báo cáo hợp lệ từ 3 tài khoản uy tín
        foreach (range(1, 3) as $i) {
            $reporter = NguoiDung::factory()->create(['diem_uy_tin' => 100]);
            $recalculator->createReport($doc, $reporter, ['ly_do' => 'KHAC']);
        }

        $doc->refresh();
        $this->assertSame('CHO_KIEM_TRA', $doc->trang_thai);

        Notification::assertSentTo($admin, DocumentReviewRequiredNotification::class, function ($n) {
            $data = $n->toDatabase(new NguoiDung());
            return $data->data['title'] === 'Tài liệu đạt ngưỡng tự động chuyển sang chờ kiểm tra';
        });
    }

    public function test_admin_and_moderator_notified_when_removal_request_submitted(): void
    {
        Notification::fake();

        $author = NguoiDung::factory()->create();
        $mod = NguoiDung::factory()->moderator()->create();
        $doc = $this->createDocument($author);
        $doc->update(['luot_tai' => 15]); // đạt ngưỡng sở hữu cộng đồng

        $this->actingAs($author)->post(route('documents.request-removal', $doc), [
            'reason' => 'Tai lieu bi loi thoi va khong con chinh xac.',
        ]);

        Notification::assertSentTo($mod, DocumentRemovalRequestSubmittedNotification::class, function ($n) {
            $data = $n->toDatabase(new NguoiDung());
            return $data->data['title'] === 'Có yêu cầu gỡ tài liệu mới';
        });
    }

    public function test_admin_notified_on_successful_raw_students_import(): void
    {
        Notification::fake();

        $admin = NguoiDung::factory()->admin()->create();
        $action = app(ImportRawStudentsAction::class);

        $records = [
            [
                'mssv' => 'DTH215111',
                'ho_ten' => 'Nguyen Van A',
                'email_sv' => 'dth215111@student.agu.edu.vn',
            ],
            [
                'mssv' => 'DTH215222',
                'ho_ten' => 'Tran Thi B',
                'email_sv' => 'dth215222@student.agu.edu.vn',
            ],
        ];

        $result = $action->execute($records, $admin);

        $this->assertSame(2, $result['total']);
        $this->assertSame(2, $result['created']);

        Notification::assertSentTo($admin, StudentRosterImportedNotification::class, function ($n) {
            $data = $n->toDatabase(new NguoiDung());
            return $data->data['title'] === 'Có dữ liệu sinh viên mới được nạp' &&
                $data->data['total'] === 2;
        });
    }

    public function test_admin_notified_on_errors_or_duplicates_during_raw_students_import(): void
    {
        Notification::fake();

        $admin = NguoiDung::factory()->admin()->create();
        $action = app(ImportRawStudentsAction::class);

        $records = [
            [
                'mssv' => 'DTH225001',
                'ho_ten' => 'Sinh Vien Hop Le',
            ],
            [
                'mssv' => '', // Lỗi thiếu MSSV
                'ho_ten' => 'Sinh Vien Khong Co MSSV',
            ],
            [
                'mssv' => 'DTH225001', // Trùng lặp MSSV trong batch
                'ho_ten' => 'Sinh Vien Trung Lap',
            ],
        ];

        $result = $action->execute($records, $admin);

        $this->assertCount(1, $result['errors']);
        $this->assertCount(1, $result['duplicates']);

        Notification::assertSentTo($admin, StudentRosterImportWarningNotification::class, function ($n) {
            $data = $n->toDatabase(new NguoiDung());
            return $data->data['title'] === 'Có lỗi hoặc bản ghi trùng trong quá trình import' &&
                $data->data['error_count'] === 1 &&
                $data->data['duplicate_count'] === 1;
        });
    }

    public function test_admin_and_moderator_notified_on_expiring_trash_documents(): void
    {
        Notification::fake();

        $admin = NguoiDung::factory()->admin()->create();
        $doc = $this->createDocument();

        // Xóa mềm tài liệu với hạn lưu sắp hết (còn 2 ngày)
        $doc->update([
            'ngay_xoa_tam' => now()->subDays(58),
            'han_luu_thung_rac' => now()->addDays(2),
        ]);
        $doc->delete();

        $action = app(CheckExpiringTrashDocumentsAction::class);
        $result = $action->execute(7);

        $this->assertGreaterThanOrEqual(1, $result['count']);

        Notification::assertSentTo($admin, DocumentsExpiringInTrashNotification::class, function ($n) {
            $data = $n->toDatabase(new NguoiDung());
            return $data->data['title'] === 'Có tài liệu sắp hết thời gian lưu trong thùng rác';
        });
    }

    // =========================================================================
    // 2. CÁC LUỒNG THÔNG BÁO CHO SINH VIÊN ĐANG HỌC
    // =========================================================================

    public function test_current_student_receives_first_time_password_change_notification(): void
    {
        Notification::fake();

        DanhSachSinhVienRaw::create([
            'mssv' => 'DTH230001',
            'ho_ten' => 'Sinh Vien Moi',
            'email_sv' => 'dth230001@student.agu.edu.vn',
            'mat_khau_mac_dinh' => Hash::make('Agu@123456'),
        ]);

        // Đăng nhập lần đầu bằng tài khoản đối soát
        $response = $this->post(route('login'), [
            'email' => 'DTH230001',
            'password' => 'Agu@123456',
        ]);

        $response->assertRedirect(route('password.change'));

        $student = NguoiDung::where('mssv', 'DTH230001')->firstOrFail();
        Notification::assertSentTo($student, FirstTimePasswordChangeRequiredNotification::class, function ($n) {
            $data = $n->toDatabase(new NguoiDung());
            return $data->data['title'] === 'Thông báo yêu cầu đổi mật khẩu lần đầu';
        });
    }

    public function test_current_student_receives_account_activated_notification_after_password_change(): void
    {
        Notification::fake();

        $student = NguoiDung::factory()->create([
            'mssv' => 'DTH230002',
            'trang_thai' => TrangThaiNguoiDung::CHUA_XAC_THUC,
            'vai_tro' => VaiTroNguoiDung::SINH_VIEN,
        ]);

        DanhSachSinhVienRaw::create([
            'mssv' => 'DTH230002',
            'ho_ten' => $student->ho_ten,
            'mat_khau_mac_dinh' => Hash::make('OldPass@123'),
        ]);

        $action = app(ChangeFirstTimePasswordAction::class);
        $action->execute($student, 'NewSuperPass@2026');

        Notification::assertSentTo($student, StudentAccountActivatedNotification::class, function ($n) {
            $data = $n->toDatabase(new NguoiDung());
            return $data->data['title'] === 'Thông báo kích hoạt tài khoản thành công';
        });
    }

    public function test_current_student_cannot_submit_kyc_and_does_not_receive_kyc_notifications(): void
    {
        Notification::fake();

        // Đưa MSSV vào danh sách sinh viên hiện hành
        DanhSachSinhVienRaw::create([
            'mssv' => 'DTH240001',
            'ho_ten' => 'Sinh Vien Chinh Quy',
            'email_sv' => 'dth240001@student.agu.edu.vn',
            'mat_khau_mac_dinh' => Hash::make('Agu@123456'),
        ]);

        $action = app(SubmitAlumniKycAction::class);
        $file = UploadedFile::fake()->image('proof.jpg');

        $this->expectException(ValidationException::class);

        try {
            $action->execute([
                'ho_ten' => 'Sinh Vien Chinh Quy',
                'email' => 'sv@example.com',
                'mssv' => 'DTH240001',
                'nam_tot_nghiep' => 2026,
                'mat_khau' => 'Pass@123',
                'loai_minh_chung' => 'BANG_TOT_NGHIEP',
            ], $file);
        } finally {
            // Khẳng định không có notification KYC nào được gửi đi
            Notification::assertNothingSent();
        }
    }

    // =========================================================================
    // 3. CÁC LUỒNG THÔNG BÁO CHO CỰU SINH VIÊN
    // =========================================================================

    public function test_alumni_receives_approval_notification_stating_allowed_login(): void
    {
        Notification::fake();

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

        $action = app(ApproveKycAction::class);
        $action->execute($kyc->ma_yeu_cau_xac_thuc, $admin);

        Notification::assertSentTo($alumni, AlumniKycDecisionNotification::class, function ($n) {
            $data = $n->toDatabase(new NguoiDung());
            return $data->data['decision'] === 'DA_DUYET' &&
                $data->data['can_login'] === true &&
                str_contains($data->data['message'], 'được phép đăng nhập');
        });
    }

    public function test_alumni_receives_rejection_notification_with_reason(): void
    {
        Notification::fake();

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

        $action = app(RejectKycAction::class);
        $action->execute($kyc->ma_yeu_cau_xac_thuc, 'Ảnh văn bằng không nhìn rõ số hiệu', $admin);

        Notification::assertSentTo($alumni, AlumniKycDecisionNotification::class, function ($n) {
            $data = $n->toDatabase(new NguoiDung());
            return $data->data['decision'] === 'TU_CHOI' &&
                $data->data['can_login'] === false &&
                str_contains($data->data['message'], 'Ảnh văn bằng không nhìn rõ số hiệu');
        });
    }

    public function test_alumni_pending_kyc_receives_clear_message_on_login_attempt(): void
    {
        $alumni = NguoiDung::factory()->alumni()->create([
            'email' => 'alumni_pending_test@agu.edu.vn',
            'mat_khau' => 'Password@123',
            'trang_thai' => TrangThaiNguoiDung::CHUA_XAC_THUC,
        ]);

        YeuCauXacThuc::create([
            'ma_nguoi_dung' => $alumni->ma_nguoi_dung,
            'loai_xac_thuc' => LoaiXacThuc::CUU_SINH_VIEN,
            'trang_thai' => TrangThaiXacThuc::CHO_DUYET,
            'mssv' => $alumni->mssv,
            'nam_tot_nghiep' => $alumni->nam_tot_nghiep,
            'duong_dan_minh_chung' => 'private/kyc/proof.jpg',
        ]);

        $response = $this->post(route('login'), [
            'email' => 'alumni_pending_test@agu.edu.vn',
            'password' => 'Password@123',
        ]);

        $response->assertSessionHasErrors('email');
        $response->assertSessionHas('kyc_status');
        $kycStatus = session('kyc_status');
        $this->assertStringContainsString('được phép đăng nhập sau khi hồ sơ được duyệt', $kycStatus['message']);
    }

    // =========================================================================
    // 4. API THÔNG BÁO VÀ MODEL THÔNG BÁO
    // =========================================================================

    public function test_user_can_fetch_notifications_and_mark_as_read(): void
    {
        $user = NguoiDung::factory()->create();

        // Gửi thông báo mẫu vào DB
        $user->notify(new FirstTimePasswordChangeRequiredNotification('DTH999999'));

        $this->assertSame(1, $user->unreadNotifications()->count());

        $notification = $user->unreadNotifications()->first();
        $this->assertInstanceOf(ThongBao::class, $notification);
        $this->assertSame('Thông báo yêu cầu đổi mật khẩu lần đầu', $notification->tieu_de);

        // Fetch API
        $response = $this->actingAs($user)->getJson(route('notifications.index'));
        $response->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonCount(1, 'notifications');

        // Mark single as read
        $readResponse = $this->actingAs($user)->postJson(route('notifications.read', $notification->id));
        $readResponse->assertOk()
            ->assertJsonPath('unread_count', 0);

        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());

        // Gửi thêm 2 thông báo mới
        $user->notify(new StudentAccountActivatedNotification('DTH999999', 'Nguyen Van Test'));
        $user->notify(new FirstTimePasswordChangeRequiredNotification('DTH999999'));

        $this->assertSame(2, $user->fresh()->unreadNotifications()->count());

        // Mark all as read
        $markAllResponse = $this->actingAs($user)->postJson(route('notifications.read-all'));
        $markAllResponse->assertOk()
            ->assertJsonPath('unread_count', 0);

        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());
    }
}
