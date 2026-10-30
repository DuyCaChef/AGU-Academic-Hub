<?php

namespace Tests\Feature;

use App\Models\MonHoc;
use App\Models\NguoiDung;
use App\Models\TaiLieu;
use App\Notifications\DocumentReviewRequiredNotification;
use App\Shared\Services\DocumentReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DocumentReportMonitoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_unique_reports_from_trusted_accounts_are_counted(): void
    {
        $document = $this->createDocument();
        $trusted = NguoiDung::factory()->create(['diem_uy_tin' => 100]);
        $untrusted = NguoiDung::factory()->create(['diem_uy_tin' => 99]);
        $service = app(DocumentReportService::class);

        $service->create($document, $trusted, ['ly_do' => 'SAI_NOI_DUNG']);
        $service->create($document, $untrusted, ['ly_do' => 'KHAC']);

        $this->expectException(ValidationException::class);
        $service->create($document, $trusted, ['ly_do' => 'BAN_QUYEN']);
    }

    public function test_document_is_queued_and_then_hidden_at_configured_thresholds(): void
    {
        Notification::fake();
        $document = $this->createDocument();
        $moderator = NguoiDung::factory()->moderator()->create();
        $service = app(DocumentReportService::class);

        foreach (range(1, 3) as $index) {
            $service->create($document, NguoiDung::factory()->create(), ['ly_do' => 'KHAC']);
        }

        $document->refresh();
        $this->assertSame('CHO_KIEM_TRA', $document->trang_thai);
        $this->assertSame(3, $document->so_bao_cao_hop_le);
        $this->assertStringContainsString('3 báo cáo hợp lệ', $document->ly_do_trang_thai);
        Notification::assertSentTo($moderator, DocumentReviewRequiredNotification::class);

        foreach (range(4, 5) as $index) {
            $service->create($document, NguoiDung::factory()->create(), ['ly_do' => 'KHAC']);
        }

        $document->refresh();
        $this->assertSame('TAM_AN_KIEM_TRA', $document->trang_thai);
        $this->assertSame(5, $document->so_bao_cao_hop_le);
    }

    private function createDocument(): TaiLieu
    {
        return TaiLieu::create([
            'ma_mon_hoc' => MonHoc::factory()->create()->getKey(),
            'ma_nguoi_dang' => NguoiDung::factory()->create()->getKey(),
            'tieu_de' => 'Tài liệu kiểm thử',
            'duong_dan_tep' => 'documents/test.pdf',
            'dinh_dang_tep' => 'pdf',
            'dung_luong_kb' => 10,
            'ma_bam_file' => hash('sha256', uniqid('', true)),
            'phan_loai' => 'KHAC',
            'phien_ban' => '1.0',
            'trang_thai' => 'DA_DUYET',
        ]);
    }
}
