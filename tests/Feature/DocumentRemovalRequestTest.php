<?php

namespace Tests\Feature;

use App\Models\MonHoc;
use App\Models\NguoiDung;
use App\Models\TaiLieu;
use App\Models\YeuCauGoBoTaiLieu;
use App\Notifications\DocumentRemovalRequestResolvedNotification;
use App\Notifications\DocumentRemovalRequestSubmittedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentRemovalRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_author_cannot_request_removal_before_community_ownership_threshold(): void
    {
        $author = NguoiDung::factory()->create();
        $document = $this->createDocument($author, 9);

        $response = $this->actingAs($author)->post(route('documents.request-removal', $document), [
            'reason' => 'Tôi muốn cập nhật tài liệu này.',
        ]);

        $response->assertSessionHasErrors('document');
        $this->assertDatabaseCount('yeu_cau_go_bo_tai_lieu', 0);
    }

    public function test_author_can_submit_community_removal_request_and_moderators_are_notified(): void
    {
        Notification::fake();
        $author = NguoiDung::factory()->create();
        $moderator = NguoiDung::factory()->moderator()->create();
        $document = $this->createDocument($author, 10);

        $this->actingAs($author)->post(route('documents.request-removal', $document), [
            'reason' => 'Tài liệu đã lỗi thời và cần được gỡ để tránh nhầm lẫn.',
        ])->assertSessionHasNoErrors();

        $removalRequest = YeuCauGoBoTaiLieu::firstOrFail();
        $this->assertSame('CHO_XU_LY', $removalRequest->trang_thai);
        Notification::assertSentTo($moderator, DocumentRemovalRequestSubmittedNotification::class);
    }

    public function test_admin_can_approve_request_and_document_is_soft_deleted(): void
    {
        Notification::fake();
        $author = NguoiDung::factory()->create();
        $admin = NguoiDung::factory()->admin()->create();
        $document = $this->createDocument($author, 25);
        $removalRequest = YeuCauGoBoTaiLieu::create([
            'ma_tai_lieu' => $document->getKey(),
            'ma_nguoi_yeu_cau' => $author->getKey(),
            'ly_do' => 'Tài liệu cần được gỡ theo yêu cầu của tác giả.',
        ]);

        $this->actingAs($admin)->put(route('admin.documents.removal-requests.decide', $removalRequest), [
            'action' => 'APPROVE',
        ])->assertSessionHasNoErrors();

        $this->assertSoftDeleted('tai_lieu', ['ma_tai_lieu' => $document->getKey()]);
        $this->assertDatabaseHas('yeu_cau_go_bo_tai_lieu', [
            'ma_yeu_cau_go_bo' => $removalRequest->getKey(),
            'trang_thai' => 'DA_DUYET',
            'ma_nguoi_xu_ly' => $admin->getKey(),
        ]);
        $this->assertDatabaseHas('tai_lieu', [
            'ma_tai_lieu' => $document->getKey(),
            'ma_nguoi_xoa' => $admin->getKey(),
            'nguon_xoa' => 'TAC_GIA',
            'ly_do_xoa' => 'Tài liệu cần được gỡ theo yêu cầu của tác giả.',
        ]);
        $this->assertDatabaseHas('lich_su_thung_rac', [
            'ma_tai_lieu' => $document->getKey(),
            'hanh_dong' => 'SOFT_DELETE',
            'nguon' => 'TAC_GIA',
        ]);
        Notification::assertSentTo($author, DocumentRemovalRequestResolvedNotification::class);
    }

    public function test_moderator_must_confirm_pending_violation_before_restore(): void
    {
        $moderator = NguoiDung::factory()->moderator()->create();
        $author = NguoiDung::factory()->create();
        $reporter = NguoiDung::factory()->create();
        $document = $this->createDocument($author, 0);
        $document->forceFill([
            'ma_nguoi_xoa' => $moderator->getKey(),
            'nguon_xoa' => 'MODERATOR',
            'han_luu_thung_rac' => now()->addDays(60),
        ])->save();
        $document->delete();
        \App\Models\BaoCaoViPham::create([
            'ma_tai_lieu' => $document->getKey(),
            'ma_nguoi_bao_cao' => $reporter->getKey(),
            'ly_do' => 'MA_DOC',
            'trang_thai_xu_ly' => 'CHO_XU_LY',
        ]);

        $this->actingAs($moderator)
            ->post(route('admin.documents.restore', $document), [])
            ->assertSessionHasErrors('document');

        $this->actingAs($moderator)
            ->post(route('admin.documents.restore', $document), ['confirm_violation' => true])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tai_lieu', [
            'ma_tai_lieu' => $document->getKey(),
            'trang_thai' => 'CHO_KIEM_TRA',
        ]);
        $this->assertDatabaseHas('lich_su_thung_rac', [
            'ma_tai_lieu' => $document->getKey(),
            'hanh_dong' => 'RESTORE',
        ]);
    }

    public function test_expired_documents_are_physically_removed_and_audited(): void
    {
        Storage::fake('local');
        $admin = NguoiDung::factory()->admin()->create();
        $document = $this->createDocument(NguoiDung::factory()->create(), 0);
        Storage::disk('local')->put($document->duong_dan_tep, 'test');
        $document->forceFill([
            'ma_nguoi_xoa' => $admin->getKey(),
            'nguon_xoa' => 'ADMIN',
            'han_luu_thung_rac' => now()->subDays(90),
        ])->save();
        $document->delete();
        $document->forceFill(['ngay_xoa_tam' => now()->subDays(91)])->save();

        $this->artisan('documents:purge-trash')->assertSuccessful();

        $this->assertDatabaseMissing('tai_lieu', ['ma_tai_lieu' => $document->getKey()]);
        $this->assertDatabaseHas('lich_su_thung_rac', [
            'ma_tai_lieu' => $document->getKey(),
            'hanh_dong' => 'PERMANENT_DELETE',
            'nguon' => 'SYSTEM',
        ]);
        Storage::disk('local')->assertMissing($document->duong_dan_tep);
    }

    public function test_admin_can_permanently_delete_after_sixty_days(): void
    {
        Storage::fake('local');
        $admin = NguoiDung::factory()->admin()->create();
        $document = $this->createDocument(NguoiDung::factory()->create(), 0);
        Storage::disk('local')->put($document->duong_dan_tep, 'test');
        $document->forceFill([
            'ma_nguoi_xoa' => $admin->getKey(),
            'nguon_xoa' => 'ADMIN',
            'han_luu_thung_rac' => now()->subDay(),
        ])->save();
        $document->delete();
        $document->forceFill(['ngay_xoa_tam' => now()->subDays(61)])->save();

        $this->actingAs($admin)
            ->delete(route('admin.documents.permanent-delete', $document), [
                'reason' => 'Admin xóa thủ công sau thời hạn 60 ngày.',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('tai_lieu', ['ma_tai_lieu' => $document->getKey()]);
        Storage::disk('local')->assertMissing($document->duong_dan_tep);
    }

    public function test_rejection_requires_a_reason_and_records_processor(): void
    {
        $author = NguoiDung::factory()->create();
        $moderator = NguoiDung::factory()->moderator()->create();
        $document = $this->createDocument($author, 10);
        $removalRequest = YeuCauGoBoTaiLieu::create([
            'ma_tai_lieu' => $document->getKey(),
            'ma_nguoi_yeu_cau' => $author->getKey(),
            'ly_do' => 'Tài liệu cần được gỡ vì có phiên bản mới.',
        ]);

        $this->actingAs($moderator)
            ->put(route('admin.documents.removal-requests.decide', $removalRequest), ['action' => 'REJECT'])
            ->assertSessionHasErrors('decision_reason');

        $this->actingAs($moderator)
            ->put(route('admin.documents.removal-requests.decide', $removalRequest), [
                'action' => 'REJECT',
                'decision_reason' => 'Tài liệu vẫn đang được cộng đồng sử dụng.',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('yeu_cau_go_bo_tai_lieu', [
            'ma_yeu_cau_go_bo' => $removalRequest->getKey(),
            'trang_thai' => 'TU_CHOI',
            'ma_nguoi_xu_ly' => $moderator->getKey(),
            'ly_do_tu_choi' => 'Tài liệu vẫn đang được cộng đồng sử dụng.',
        ]);
        $this->assertNotNull($removalRequest->fresh()->xu_ly_luc);
        $this->assertDatabaseHas('tai_lieu', ['ma_tai_lieu' => $document->getKey(), 'ngay_xoa_tam' => null]);
    }

    private function createDocument(NguoiDung $author, int $downloads): TaiLieu
    {
        return TaiLieu::create([
            'ma_mon_hoc' => MonHoc::factory()->create()->getKey(),
            'ma_nguoi_dang' => $author->getKey(),
            'tieu_de' => 'Tài liệu kiểm thử',
            'duong_dan_tep' => 'documents/test.pdf',
            'dinh_dang_tep' => 'pdf',
            'dung_luong_kb' => 100,
            'ma_bam_file' => hash('sha256', uniqid('', true)),
            'phan_loai' => 'KHAC',
            'luot_tai' => $downloads,
            'trang_thai' => 'DA_DUYET',
        ]);
    }
}
