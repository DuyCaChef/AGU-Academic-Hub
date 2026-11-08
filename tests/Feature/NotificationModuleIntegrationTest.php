<?php

namespace Tests\Feature;

use App\Enums\TrangThaiTaiLieu;
use App\Enums\TrangThaiYeuCauTaiLieu;
use App\Enums\VaiTroNguoiDung;
use App\Models\DanhGiaMonHoc;
use App\Models\Khoa;
use App\Models\MonHoc;
use App\Models\NguoiDung;
use App\Models\TaiLieu;
use App\Models\ThongBao;
use App\Models\YeuCauTaiLieu;
use App\Modules\Bounties\Application\Actions\CancelBountyRequestAction;
use App\Modules\Bounties\Application\Actions\ConfirmBountyFulfillmentAction;
use App\Modules\Bounties\Application\Actions\FulfillBountyRequestAction;
use App\Modules\Moderation\Application\Actions\DecideDocumentReviewAction;
use App\Modules\Notifications\Application\Contracts\NotificationDispatcherInterface;
use App\Modules\Notifications\Application\DTOs\NotificationPayload;
use App\Modules\Notifications\Domain\Enums\NotificationCategory;
use App\Modules\Notifications\Domain\Enums\NotificationType;
use App\Modules\Reviews\Application\Actions\ReportReviewViolationAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NotificationModuleIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected Khoa $faculty;
    protected MonHoc $course;
    protected NguoiDung $studentA;
    protected NguoiDung $studentB;
    protected NguoiDung $moderator;
    protected NguoiDung $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->faculty = Khoa::create([
            'ten_khoa'     => 'Khoa Công nghệ Thông tin',
            'ky_hieu_khoa' => 'CNTT',
        ]);

        $this->course = MonHoc::create([
            'ma_khoa'     => $this->faculty->ma_khoa,
            'ma_hoc_phan' => 'COS101',
            'ten_mon_hoc' => 'Lập trình Cơ bản',
            'so_tin_chi'  => 3,
            'mo_ta'       => 'Môn học cơ sở lập trình',
        ]);

        $this->studentA = NguoiDung::factory()->create([
            'ho_ten'      => 'Nguyễn Văn Sinh Viên A',
            'ma_khoa'     => $this->faculty->ma_khoa,
            'vai_tro'     => VaiTroNguoiDung::SINH_VIEN,
            'diem_uy_tin' => 100,
        ]);

        $this->studentB = NguoiDung::factory()->create([
            'ho_ten'      => 'Trần Thị Sinh Viên B',
            'ma_khoa'     => $this->faculty->ma_khoa,
            'vai_tro'     => VaiTroNguoiDung::SINH_VIEN,
            'diem_uy_tin' => 50,
        ]);

        $this->moderator = NguoiDung::factory()->create([
            'ho_ten'      => 'Phạm Kiểm Duyệt Viên',
            'ma_khoa'     => $this->faculty->ma_khoa,
            'vai_tro'     => VaiTroNguoiDung::KIEM_DUYET_VIEN,
        ]);

        $this->admin = NguoiDung::factory()->create([
            'ho_ten'      => 'Hoàng Quản Trị Viên',
            'ma_khoa'     => $this->faculty->ma_khoa,
            'vai_tro'     => VaiTroNguoiDung::QUAN_TRI_VIEN,
        ]);
    }

    // =========================================================================
    // 1. NOTIFICATION CORE USE CASES (UC-NOT-01 ĐẾN UC-NOT-06)
    // =========================================================================

    public function test_uc_not_01_user_can_view_own_paginated_notifications_and_filter(): void
    {
        /** @var NotificationDispatcherInterface $dispatcher */
        $dispatcher = app(NotificationDispatcherInterface::class);

        // Tạo 3 thông báo cho Student A (2 chưa đọc, 1 đã đọc)
        $payload1 = new NotificationPayload(
            type: NotificationType::SYSTEM_ALERT,
            category: NotificationCategory::PERSONAL,
            title: 'Thông báo 1 chưa đọc',
            message: 'Nội dung thông báo 1',
            actionUrl: '/tai-lieu'
        );
        $payload2 = new NotificationPayload(
            type: NotificationType::BOUNTY_CONFIRMED,
            category: NotificationCategory::PERSONAL,
            title: 'Thông báo 2 chưa đọc',
            message: 'Nội dung thông báo 2',
            actionUrl: '/yeu-cau-tai-lieu/1'
        );
        $payload3 = new NotificationPayload(
            type: NotificationType::DOCUMENT_APPROVED,
            category: NotificationCategory::PERSONAL,
            title: 'Thông báo 3 đã đọc',
            message: 'Nội dung thông báo 3',
            actionUrl: '/tai-lieu/1'
        );

        $dispatcher->dispatch($this->studentA, $payload1);
        $dispatcher->dispatch($this->studentA, $payload2);
        $dispatcher->dispatch($this->studentA, $payload3);

        // Đánh dấu thông báo 3 đã đọc
        $notif3 = $this->studentA->notifications()->where('tieu_de', 'Thông báo 3 đã đọc')->first();
        $notif3->markAsRead();

        // 1. Lọc tất cả (JSON API)
        $resAll = $this->actingAs($this->studentA)->getJson(route('notifications.index'));
        $resAll->assertOk();
        $resAll->assertJsonCount(3, 'notifications');
        $resAll->assertJsonPath('unread_count', 2);

        // 2. Lọc chưa đọc (unread)
        $resUnread = $this->actingAs($this->studentA)->getJson(route('notifications.index', ['filter' => 'unread']));
        $resUnread->assertOk();
        $resUnread->assertJsonCount(2, 'notifications');

        // 3. Lọc đã đọc (read)
        $resRead = $this->actingAs($this->studentA)->getJson(route('notifications.index', ['filter' => 'read']));
        $resRead->assertOk();
        $resRead->assertJsonCount(1, 'notifications');
        $resRead->assertJsonPath('notifications.0.title', 'Thông báo 3 đã đọc');

        // 4. Truy cập giao diện Inertia (Notification Center)
        $resPage = $this->actingAs($this->studentA)->get(route('notifications.index'));
        $resPage->assertOk();
        $resPage->assertInertia(fn ($page) => $page
            ->component('Notifications/Index')
            ->has('notifications.data', 3)
            ->where('unread_count', 2)
        );
    }

    public function test_uc_not_02_unread_count_api_returns_exact_count(): void
    {
        $dispatcher = app(NotificationDispatcherInterface::class);

        $this->assertEquals(0, $this->studentA->unreadNotifications()->count());

        $payload = new NotificationPayload(
            type: NotificationType::SYSTEM_ALERT,
            category: NotificationCategory::PERSONAL,
            title: 'Thông báo test đếm',
            message: 'Nội dung'
        );
        $dispatcher->dispatch($this->studentA, $payload);

        $response = $this->actingAs($this->studentA)->getJson(route('notifications.unread-count'));
        $response->assertOk();
        $response->assertJson(['unread_count' => 1]);
    }

    public function test_uc_not_03_mark_as_read_is_idempotent_and_updates_unread_count(): void
    {
        $dispatcher = app(NotificationDispatcherInterface::class);
        $payload = new NotificationPayload(
            type: NotificationType::SYSTEM_ALERT,
            category: NotificationCategory::PERSONAL,
            title: 'Cần đọc',
            message: 'Đọc ngay'
        );
        $dispatcher->dispatch($this->studentA, $payload);

        $notif = $this->studentA->unreadNotifications()->first();
        $this->assertNotNull($notif);

        // Gọi lần 1
        $res1 = $this->actingAs($this->studentA)->postJson(route('notifications.read', $notif->id));
        $res1->assertOk();
        $res1->assertJson(['success' => true, 'unread_count' => 0]);
        $this->assertNotNull($notif->fresh()->da_doc_luc);

        // Gọi lần 2 (Idempotency - không gây lỗi)
        $res2 = $this->actingAs($this->studentA)->postJson(route('notifications.read', $notif->id));
        $res2->assertOk();
        $res2->assertJson(['success' => true, 'unread_count' => 0]);
    }

    public function test_uc_not_04_mark_all_as_read_updates_all_unread(): void
    {
        $dispatcher = app(NotificationDispatcherInterface::class);
        for ($i = 1; $i <= 3; $i++) {
            $dispatcher->dispatch($this->studentA, new NotificationPayload(
                type: NotificationType::SYSTEM_ALERT,
                category: NotificationCategory::PERSONAL,
                title: "Thông báo {$i}",
                message: "Nội dung {$i}"
            ));
        }

        $this->assertEquals(3, $this->studentA->unreadNotifications()->count());

        $response = $this->actingAs($this->studentA)->postJson(route('notifications.read-all'));
        $response->assertOk();
        $response->assertJson(['success' => true, 'unread_count' => 0]);

        $this->assertEquals(0, $this->studentA->fresh()->unreadNotifications()->count());
    }

    public function test_uc_not_05_user_can_delete_own_notification(): void
    {
        $dispatcher = app(NotificationDispatcherInterface::class);
        $dispatcher->dispatch($this->studentA, new NotificationPayload(
            type: NotificationType::SYSTEM_ALERT,
            category: NotificationCategory::PERSONAL,
            title: 'Sắp bị xóa',
            message: 'Tạm biệt'
        ));

        $notif = $this->studentA->notifications()->first();
        $this->assertNotNull($notif);

        $response = $this->actingAs($this->studentA)->deleteJson(route('notifications.destroy', $notif->id));
        $response->assertOk();
        $response->assertJson(['success' => true]);

        $this->assertDatabaseMissing('notifications', [
            'id' => $notif->id,
        ]);
    }

    public function test_uc_not_06_action_url_sanitizes_open_redirects(): void
    {
        // 1. URL an toàn tương đối
        $safePayload = new NotificationPayload(
            type: NotificationType::SYSTEM_ALERT,
            category: NotificationCategory::PERSONAL,
            title: 'An toàn',
            message: 'OK',
            actionUrl: '/mon-hoc/COS101'
        );
        $this->assertEquals('/mon-hoc/COS101', $safePayload->actionUrl);

        // 2. URL độc hại (Open Redirect tới domain bên ngoài)
        $maliciousPayload1 = new NotificationPayload(
            type: NotificationType::SYSTEM_ALERT,
            category: NotificationCategory::PERSONAL,
            title: 'Độc hại',
            message: 'Cố tình lừa đảo',
            actionUrl: 'https://evil-phishing.com/login'
        );
        $this->assertNull($maliciousPayload1->actionUrl);

        // 3. Protocol-relative URL (//evil.com)
        $maliciousPayload2 = new NotificationPayload(
            type: NotificationType::SYSTEM_ALERT,
            category: NotificationCategory::PERSONAL,
            title: 'Độc hại 2',
            message: 'Protocol relative',
            actionUrl: '//evil-site.com'
        );
        $this->assertNull($maliciousPayload2->actionUrl);
    }

    // =========================================================================
    // 2. NEGATIVE TESTS BẮT BUỘC (ISOLATION & SECURITY)
    // =========================================================================

    public function test_cross_user_isolation_user_a_cannot_view_user_b_notifications(): void
    {
        $dispatcher = app(NotificationDispatcherInterface::class);
        $dispatcher->dispatch($this->studentB, new NotificationPayload(
            type: NotificationType::BOUNTY_CONFIRMED,
            category: NotificationCategory::PERSONAL,
            title: 'Thông báo bí mật của B',
            message: 'Dữ liệu cá nhân'
        ));

        // Student A xem danh sách của mình -> Không thấy thông báo của B
        $response = $this->actingAs($this->studentA)->getJson(route('notifications.index'));
        $response->assertOk();
        $response->assertJsonCount(0, 'notifications');
    }

    public function test_cross_user_isolation_user_a_cannot_mark_as_read_or_delete_user_b_notification(): void
    {
        $dispatcher = app(NotificationDispatcherInterface::class);
        $dispatcher->dispatch($this->studentB, new NotificationPayload(
            type: NotificationType::SYSTEM_ALERT,
            category: NotificationCategory::PERSONAL,
            title: 'Thông báo của B',
            message: 'Cấm A can thiệp'
        ));

        $notifB = $this->studentB->notifications()->first();

        // Student A cố tình gửi POST đánh dấu đã đọc thông báo của B -> 404 Not Found
        $resRead = $this->actingAs($this->studentA)->postJson(route('notifications.read', $notifB->id));
        $resRead->assertStatus(404);
        $this->assertTrue($notifB->fresh()->unread());

        // Student A cố tình gửi DELETE xóa thông báo của B -> 404 Not Found
        $resDelete = $this->actingAs($this->studentA)->deleteJson(route('notifications.destroy', $notifB->id));
        $resDelete->assertStatus(404);
        $this->assertDatabaseHas('notifications', ['id' => $notifB->id]);
    }

    public function test_guest_cannot_access_notification_endpoints(): void
    {
        $this->getJson(route('notifications.index'))->assertStatus(401);
        $this->getJson(route('notifications.unread-count'))->assertStatus(401);
        $this->postJson(route('notifications.read', 'fake-uuid'))->assertStatus(401);
        $this->postJson(route('notifications.read-all'))->assertStatus(401);
        $this->deleteJson(route('notifications.destroy', 'fake-uuid'))->assertStatus(401);
    }

    public function test_idempotency_prevents_duplicate_notifications_on_queue_retry(): void
    {
        $dispatcher = app(NotificationDispatcherInterface::class);

        $payload = new NotificationPayload(
            type: NotificationType::BOUNTY_CONTRIBUTED,
            category: NotificationCategory::PERSONAL,
            title: 'Thông báo chống gửi trùng',
            message: 'Chỉ gửi 1 lần duy nhất',
            idempotencyKey: 'test_idempotent_key_12345'
        );

        // Lần 1: Thành công
        $result1 = $dispatcher->dispatch($this->studentA, $payload);
        $this->assertTrue($result1);
        $this->assertEquals(1, $this->studentA->notifications()->count());

        // Lần 2: Thử gửi lại với cùng idempotencyKey (mô phỏng worker retry)
        $result2 = $dispatcher->dispatch($this->studentA, $payload);
        $this->assertTrue($result2);

        // Số lượng thông báo trong DB vẫn chỉ là 1, không bị duplicate!
        $this->assertEquals(1, $this->studentA->notifications()->count());
    }

    // =========================================================================
    // 3. BUSINESS WORKFLOW INTEGRATION (PHASE 2 & 3: BOUNTY, DOC, REVIEW)
    // =========================================================================

    public function test_uc_st_01_and_uc_mod_06_bounty_fulfillment_sends_notifications(): void
    {
        Storage::fake('local');

        // Student A tạo Bounty 40 điểm
        $yeuCau = YeuCauTaiLieu::create([
            'ma_mon_hoc'   => $this->course->ma_mon_hoc,
            'ma_nguoi_tao' => $this->studentA->ma_nguoi_dung,
            'tieu_de'      => 'Cần tìm slide bài giảng C++',
            'phan_loai'    => 'BAI_GIANG',
            'diem_thuong'  => 40,
            'trang_thai'   => TrangThaiYeuCauTaiLieu::MO,
        ]);

        $fakeFile = UploadedFile::fake()->create('slide_cpp.pdf', 200, 'application/pdf');

        // Student B nộp tài liệu vào yêu cầu qua Method B
        /** @var FulfillBountyRequestAction $fulfillAction */
        $fulfillAction = app(FulfillBountyRequestAction::class);
        $result = $fulfillAction->execute(
            $this->studentB,
            $yeuCau->ma_yeu_cau,
            [
                'tieu_de'          => 'Slide C++ Chuẩn Đại Cương',
                'phan_loai'        => 'BAI_GIANG',
                'ghi_chu_dong_gop' => 'Tài liệu đầy đủ',
            ],
            $fakeFile
        );

        $this->assertTrue($result['success']);

        // 1. Kiểm tra Requester (Student A) nhận được thông báo UC-ST-01
        $requesterNotif = $this->studentA->notifications()->first();
        $this->assertNotNull($requesterNotif);
        $this->assertStringContainsString('Có tài liệu đóng góp', $requesterNotif->tieu_de);
        // Vì tài liệu Method B upload mới nên trang_thai = CHO_DUYET -> Phải thông báo rõ đang chờ duyệt
        $this->assertStringContainsString('chờ Ban kiểm duyệt phê duyệt', $requesterNotif->noi_dung);

        // 2. Kiểm tra Moderator nhận được thông báo kiểm duyệt UC-MOD-06
        $modNotif = $this->moderator->notifications()->first();
        $this->assertNotNull($modNotif);
        $this->assertStringContainsString('Tài liệu đóng góp săn thưởng cần kiểm duyệt', $modNotif->tieu_de);

        // 3. Đảm bảo Student B (người nộp) không bị gửi nhầm thông báo của Requester
        $this->assertEquals(0, $this->studentB->notifications()->count());
    }

    public function test_uc_st_04_and_uc_st_05_bounty_confirmation_notifies_both_parties(): void
    {
        // Setup Bounty
        $this->studentA->viDiem->update(['diem_kha_dung' => 60, 'diem_tam_giu' => 40]);
        $this->studentB->viDiem->update(['diem_kha_dung' => 100, 'diem_tam_giu' => 0]);

        $yeuCau = YeuCauTaiLieu::create([
            'ma_mon_hoc'   => $this->course->ma_mon_hoc,
            'ma_nguoi_tao' => $this->studentA->ma_nguoi_dung,
            'tieu_de'      => 'Cần tìm đề thi giải thuật',
            'phan_loai'    => 'DE_THI',
            'diem_thuong'  => 40,
            'trang_thai'   => TrangThaiYeuCauTaiLieu::MO,
        ]);

        $docApproved = TaiLieu::create([
            'ma_mon_hoc'    => $this->course->ma_mon_hoc,
            'ma_nguoi_dang' => $this->studentB->ma_nguoi_dung,
            'tieu_de'       => 'Đề thi đã duyệt',
            'duong_dan_tep' => 'documents/dethi.pdf',
            'dinh_dang_tep' => 'pdf',
            'dung_luong_kb' => 100,
            'ma_bam_file'   => hash('sha256', 'dethi_content'),
            'phan_loai'     => 'DE_THI',
            'luot_tai'      => 0,
            'trang_thai'    => TrangThaiTaiLieu::DA_DUYET->value,
        ]);

        $dapUng = $yeuCau->dapUngs()->create([
            'ma_tai_lieu'       => $docApproved->ma_tai_lieu,
            'ma_nguoi_dong_gop' => $this->studentB->ma_nguoi_dung,
            'trang_thai'        => 'CHO_DUYET',
        ]);

        // Student A xác nhận nghiệm thu
        /** @var ConfirmBountyFulfillmentAction $confirmAction */
        $confirmAction = app(ConfirmBountyFulfillmentAction::class);
        $result = $confirmAction->execute($this->studentA, $yeuCau->ma_yeu_cau, $dapUng->ma_dap_ung);
        $this->assertTrue($result['success']);

        // Student A nhận thông báo nghiệm thu thành công (UC-ST-04)
        $notifA = $this->studentA->notifications()->latest()->first();
        $this->assertNotNull($notifA);
        $this->assertStringContainsString('Nghiệm thu yêu cầu tài liệu thành công', $notifA->tieu_de);

        // Student B nhận thông báo nhận điểm thưởng (UC-ST-05)
        $notifB = $this->studentB->notifications()->latest()->first();
        $this->assertNotNull($notifB);
        $this->assertStringContainsString('Bạn đã nhận được điểm thưởng Bounty', $notifB->tieu_de);
        $this->assertStringContainsString('+40 điểm thưởng', $notifB->noi_dung);
    }

    public function test_uc_st_06_and_uc_st_07_bounty_cancellation_notifies_requester_and_contributors(): void
    {
        $this->studentA->viDiem->update(['diem_kha_dung' => 70, 'diem_tam_giu' => 30]);

        $yeuCau = YeuCauTaiLieu::create([
            'ma_mon_hoc'   => $this->course->ma_mon_hoc,
            'ma_nguoi_tao' => $this->studentA->ma_nguoi_dung,
            'tieu_de'      => 'Yêu cầu sắp hủy',
            'phan_loai'    => 'DE_THI',
            'diem_thuong'  => 30,
            'trang_thai'   => TrangThaiYeuCauTaiLieu::MO,
        ]);

        // Student B đã từng gửi submission
        $doc = TaiLieu::create([
            'ma_mon_hoc'    => $this->course->ma_mon_hoc,
            'ma_nguoi_dang' => $this->studentB->ma_nguoi_dung,
            'tieu_de'       => 'Tài liệu phản hồi',
            'duong_dan_tep' => 'documents/doc.pdf',
            'dinh_dang_tep' => 'pdf',
            'dung_luong_kb' => 100,
            'ma_bam_file'   => hash('sha256', 'doc_hash'),
            'phan_loai'     => 'DE_THI',
            'luot_tai'      => 0,
            'trang_thai'    => TrangThaiTaiLieu::DA_DUYET->value,
        ]);

        $yeuCau->dapUngs()->create([
            'ma_tai_lieu'       => $doc->ma_tai_lieu,
            'ma_nguoi_dong_gop' => $this->studentB->ma_nguoi_dung,
            'trang_thai'        => 'CHO_DUYET',
        ]);

        /** @var CancelBountyRequestAction $cancelAction */
        $cancelAction = app(CancelBountyRequestAction::class);
        $result = $cancelAction->execute($this->studentA, $yeuCau->ma_yeu_cau);
        $this->assertTrue($result['success']);

        // Requester (Student A) nhận thông báo hoàn tiền cọc (UC-ST-07)
        $notifA = $this->studentA->notifications()->latest()->first();
        $this->assertNotNull($notifA);
        $this->assertStringContainsString('Đã hoàn trả điểm cọc', $notifA->tieu_de);

        // Contributor (Student B) nhận thông báo yêu cầu đã bị hủy (UC-ST-06)
        $notifB = $this->studentB->notifications()->latest()->first();
        $this->assertNotNull($notifB);
        $this->assertStringContainsString('Yêu cầu tài liệu đã bị hủy', $notifB->tieu_de);
    }

    public function test_uc_st_02_and_uc_st_03_document_moderation_notifies_author_and_bounty_requester(): void
    {
        $yeuCau = YeuCauTaiLieu::create([
            'ma_mon_hoc'   => $this->course->ma_mon_hoc,
            'ma_nguoi_tao' => $this->studentA->ma_nguoi_dung,
            'tieu_de'      => 'Cần tài liệu ôn thi',
            'phan_loai'    => 'DE_THI',
            'diem_thuong'  => 50,
            'trang_thai'   => TrangThaiYeuCauTaiLieu::MO,
        ]);

        // Document submitted by Student B is pending moderation
        $doc = TaiLieu::create([
            'ma_mon_hoc'    => $this->course->ma_mon_hoc,
            'ma_nguoi_dang' => $this->studentB->ma_nguoi_dung,
            'tieu_de'       => 'Tài liệu đang kiểm duyệt',
            'duong_dan_tep' => 'documents/pending.pdf',
            'dinh_dang_tep' => 'pdf',
            'dung_luong_kb' => 100,
            'ma_bam_file'   => hash('sha256', 'pending_hash'),
            'phan_loai'     => 'DE_THI',
            'luot_tai'      => 0,
            'trang_thai'    => TrangThaiTaiLieu::CHO_DUYET->value,
        ]);

        $yeuCau->dapUngs()->create([
            'ma_tai_lieu'       => $doc->ma_tai_lieu,
            'ma_nguoi_dong_gop' => $this->studentB->ma_nguoi_dung,
            'trang_thai'        => 'CHO_DUYET',
        ]);

        // Moderator quyết định phê duyệt tài liệu (KEEP)
        /** @var DecideDocumentReviewAction $decideAction */
        $decideAction = app(DecideDocumentReviewAction::class);
        $decideAction->execute($doc->ma_tai_lieu, 'KEEP', 'Tài liệu chuẩn kiến thức', $this->moderator);

        // 1. Author (Student B) nhận thông báo tài liệu được phê duyệt
        $notifAuthor = $this->studentB->notifications()->latest()->first();
        $this->assertNotNull($notifAuthor);
        $this->assertStringContainsString('Tài liệu của bạn đã được phê duyệt', $notifAuthor->tieu_de);

        // 2. Requester (Student A) nhận thông báo tài liệu đóng góp đã được duyệt, sẵn sàng nghiệm thu (UC-ST-02)
        $notifReq = $this->studentA->notifications()->latest()->first();
        $this->assertNotNull($notifReq);
        $this->assertStringContainsString('Tài liệu đóng góp săn thưởng đã được duyệt', $notifReq->tieu_de);
    }

    public function test_uc_mod_03_review_reported_notifies_moderators_without_leaking_anonymous_identity(): void
    {
        $review = DanhGiaMonHoc::create([
            'ma_mon_hoc'          => $this->course->ma_mon_hoc,
            'ma_nguoi_dung'       => $this->studentB->ma_nguoi_dung,
            'hoc_ky'              => '2026-1',
            'diem_do_kho'         => 4,
            'diem_thuc_tien'      => 3,
            'diem_khoi_luong'     => 4,
            'noi_dung'            => 'Đề thi quá sức với sinh viên',
            'an_danh'             => true, // ẨN DANH!
            'trang_thai'          => 'DA_DUYET',
        ]);

        // Student A báo cáo bài review này
        /** @var ReportReviewViolationAction $reportAction */
        $reportAction = app(ReportReviewViolationAction::class);
        $reportAction->execute($this->studentA, $review->ma_danh_gia, 'SPAM', 'Nội dung trùng lặp');

        // Moderator nhận được thông báo UC-MOD-03
        $modNotif = $this->moderator->notifications()->where('tieu_de', 'like', '%báo cáo vi phạm%')->first();
        $this->assertNotNull($modNotif);
        $this->assertTrue(
            str_contains($modNotif->tieu_de, 'Đánh giá môn học bị báo cáo vi phạm') ||
            str_contains($modNotif->tieu_de, 'Báo cáo vi phạm đánh giá mới')
        );
        // Bảo vệ danh tính: Thông báo không chứa tên thật của tác giả review ẩn danh
        $this->assertStringNotContainsString($this->studentB->ho_ten, $modNotif->noi_dung);
    }
}
