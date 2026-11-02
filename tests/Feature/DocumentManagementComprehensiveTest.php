<?php

namespace Tests\Feature;

use App\Models\MonHoc;
use App\Models\NguoiDung;
use App\Models\TaiLieu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentManagementComprehensiveTest extends TestCase
{
    use RefreshDatabase;

    // Kiểm tra API kiểm tra trước trùng lặp trả về 200 khi chưa có tài liệu trùng
    public function test_check_duplicate_api_returns_ok_when_unique(): void
    {
        $user = NguoiDung::factory()->create();
        $course = MonHoc::factory()->create();

        $response = $this->actingAs($user)->postJson(route('documents.check-duplicate'), [
            'ma_mon_hoc'  => $course->ma_mon_hoc,
            'ma_bam_file' => hash('sha256', 'unique_content_string'),
        ]);

        $response->assertOk();
        $response->assertJson([
            'is_duplicate'   => false,
            'duplicate_type' => 'NONE',
        ]);
    }

    // Kiểm tra API kiểm tra trùng lặp trả về 409 khi mã băm SHA-256 đã tồn tại
    public function test_check_duplicate_api_returns_conflict_when_sha256_exists(): void
    {
        $user = NguoiDung::factory()->create();
        $course = MonHoc::factory()->create();
        $duplicateHash = hash('sha256', 'duplicate_sample_content');

        TaiLieu::create([
            'ma_mon_hoc'    => $course->ma_mon_hoc,
            'ma_nguoi_dang' => $user->getKey(),
            'tieu_de'       => 'Tai lieu goc',
            'duong_dan_tep' => 'documents/original.pdf',
            'dinh_dang_tep' => 'pdf',
            'dung_luong_kb' => 50,
            'ma_bam_file'   => $duplicateHash,
            'phan_loai'     => 'BAI_GIANG',
            'luot_tai'      => 0,
            'trang_thai'    => 'DA_DUYET',
        ]);

        $response = $this->actingAs($user)->postJson(route('documents.check-duplicate'), [
            'ma_mon_hoc'  => $course->ma_mon_hoc,
            'ma_bam_file' => $duplicateHash,
        ]);

        $response->assertStatus(409);
        $response->assertJson([
            'is_duplicate'   => true,
            'duplicate_type' => 'EXACT_SHA256',
        ]);
    }

    // Người dùng tải tài liệu mới lên thành công
    public function test_user_can_upload_document(): void
    {
        Storage::fake('local');

        $user = NguoiDung::factory()->create();
        $course = MonHoc::factory()->create();
        $file = UploadedFile::fake()->create('slide_chuong_1.pdf', 500, 'application/pdf');

        $response = $this->actingAs($user)->post(route('documents.store'), [
            'ma_mon_hoc' => $course->ma_mon_hoc,
            'tieu_de'    => 'Slide Chuong 1 Nhap Mon',
            'mo_ta'      => 'Tai lieu bai giang tuan 1',
            'phan_loai'  => 'BAI_GIANG',
            'file'       => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('tai_lieu', [
            'ma_mon_hoc'    => $course->ma_mon_hoc,
            'ma_nguoi_dang' => $user->getKey(),
            'tieu_de'       => 'Slide Chuong 1 Nhap Mon',
            'dinh_dang_tep' => 'pdf',
            'phan_loai'     => 'BAI_GIANG',
            'phien_ban'     => '1.0',
            'trang_thai'    => 'DA_DUYET',
        ]);
    }

    // Chặn tải lên khi tệp trùng lặp SHA-256 100% trong cùng môn học
    public function test_upload_fails_when_binary_sha256_is_duplicate(): void
    {
        Storage::fake('local');

        $user = NguoiDung::factory()->create();
        $course = MonHoc::factory()->create();
        $file = UploadedFile::fake()->create('sample.pdf', 300, 'application/pdf');
        $sha256 = hash_file('sha256', $file->getRealPath());

        TaiLieu::create([
            'ma_mon_hoc'    => $course->ma_mon_hoc,
            'ma_nguoi_dang' => $user->getKey(),
            'tieu_de'       => 'Tai lieu da co',
            'duong_dan_tep' => 'documents/existing.pdf',
            'dinh_dang_tep' => 'pdf',
            'dung_luong_kb' => 300,
            'ma_bam_file'   => $sha256,
            'phan_loai'     => 'KHAC',
            'luot_tai'      => 0,
            'trang_thai'    => 'DA_DUYET',
        ]);

        $response = $this->actingAs($user)->post(route('documents.store'), [
            'ma_mon_hoc' => $course->ma_mon_hoc,
            'tieu_de'    => 'Tai lieu thu hai trung ma bam',
            'file'       => $file,
        ]);

        $response->assertStatus(409);
    }

    // Tác giả có thể tạo phiên bản mới v1.1
    public function test_author_can_create_document_version(): void
    {
        Storage::fake('local');

        $user = NguoiDung::factory()->create();
        $course = MonHoc::factory()->create();

        $original = TaiLieu::create([
            'ma_mon_hoc'    => $course->ma_mon_hoc,
            'ma_nguoi_dang' => $user->getKey(),
            'tieu_de'       => 'Giao trinh goc',
            'duong_dan_tep' => 'documents/original_ver1.pdf',
            'dinh_dang_tep' => 'pdf',
            'dung_luong_kb' => 200,
            'ma_bam_file'   => hash('sha256', 'v1_seed'),
            'phan_loai'     => 'GIAO_TRINH',
            'phien_ban'     => '1.0',
            'luot_tai'      => 5,
            'trang_thai'    => 'DA_DUYET',
        ]);

        $newFile = UploadedFile::fake()->create('giao_trinh_v1_1.pdf', 250, 'application/pdf');

        $response = $this->actingAs($user)->post(route('documents.create-version', $original->getKey()), [
            'tieu_de'   => 'Giao trinh goc (Bo sung chuong 2)',
            'phien_ban' => '1.1',
            'file'      => $newFile,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('tai_lieu', [
            'ma_tai_lieu_goc' => $original->getKey(),
            'tieu_de'         => 'Giao trinh goc (Bo sung chuong 2)',
            'phien_ban'       => '1.1',
        ]);
    }

    // Tải file về máy làm tăng số lượt tải
    public function test_download_increments_download_counter(): void
    {
        Storage::fake('local');

        $user = NguoiDung::factory()->create();
        $course = MonHoc::factory()->create();
        $path = 'documents/slide_tuan_1.pdf';
        Storage::disk('local')->put($path, 'fake pdf content');

        $document = TaiLieu::create([
            'ma_mon_hoc'    => $course->ma_mon_hoc,
            'ma_nguoi_dang' => $user->getKey(),
            'tieu_de'       => 'Slide Tuan 1',
            'duong_dan_tep' => $path,
            'dinh_dang_tep' => 'pdf',
            'dung_luong_kb' => 100,
            'ma_bam_file'   => hash('sha256', 'sample_download'),
            'luot_tai'      => 0,
            'trang_thai'    => 'DA_DUYET',
        ]);

        $response = $this->actingAs($user)->get(route('documents.download', $document->getKey()));

        $response->assertOk();
        $this->assertSame(1, $document->fresh()->luot_tai);
    }

    // Xem trước tệp PDF trực tuyến
    public function test_preview_document_stream(): void
    {
        Storage::fake('local');

        $user = NguoiDung::factory()->create();
        $course = MonHoc::factory()->create();
        $path = 'documents/preview_test.pdf';
        Storage::disk('local')->put($path, 'fake pdf content');

        $document = TaiLieu::create([
            'ma_mon_hoc'    => $course->ma_mon_hoc,
            'ma_nguoi_dang' => $user->getKey(),
            'tieu_de'       => 'Tai lieu xem truoc',
            'duong_dan_tep' => $path,
            'dinh_dang_tep' => 'pdf',
            'dung_luong_kb' => 100,
            'ma_bam_file'   => hash('sha256', 'preview_test_hash'),
            'luot_tai'      => 0,
            'trang_thai'    => 'DA_DUYET',
        ]);

        $response = $this->actingAs($user)->get(route('documents.preview', $document->getKey()));

        $response->assertOk();
    }

    // Tác giả tự xóa mềm tài liệu trong 24h đầu và lượt tải = 0
    public function test_author_can_soft_delete_within_24h_and_zero_downloads(): void
    {
        $author = NguoiDung::factory()->create();
        $course = MonHoc::factory()->create();

        $document = TaiLieu::create([
            'ma_mon_hoc'    => $course->ma_mon_hoc,
            'ma_nguoi_dang' => $author->getKey(),
            'tieu_de'       => 'Tai lieu moi dang',
            'duong_dan_tep' => 'documents/new.pdf',
            'dinh_dang_tep' => 'pdf',
            'dung_luong_kb' => 100,
            'ma_bam_file'   => hash('sha256', 'soft_del_hash'),
            'luot_tai'      => 0,
            'trang_thai'    => 'DA_DUYET',
        ]);

        $response = $this->actingAs($author)->delete(route('documents.destroy', $document->getKey()), [
            'reason' => 'Toi tai len nham ban nhap.',
        ]);

        $response->assertSessionHas('success');
        $this->assertSoftDeleted('tai_lieu', ['ma_tai_lieu' => $document->getKey()]);
        $this->assertDatabaseHas('lich_su_thung_rac', [
            'ma_tai_lieu' => $document->getKey(),
            'hanh_dong'   => 'SOFT_DELETE',
            'nguon'       => 'TAC_GIA',
        ]);
    }

    // Tác giả không thể tự xóa khi tài liệu đã có lượt tải
    public function test_author_cannot_soft_delete_when_downloads_greater_than_zero(): void
    {
        $author = NguoiDung::factory()->create();
        $course = MonHoc::factory()->create();

        $document = TaiLieu::create([
            'ma_mon_hoc'    => $course->ma_mon_hoc,
            'ma_nguoi_dang' => $author->getKey(),
            'tieu_de'       => 'Tai lieu da co luot tai',
            'duong_dan_tep' => 'documents/downloaded.pdf',
            'dinh_dang_tep' => 'pdf',
            'dung_luong_kb' => 100,
            'ma_bam_file'   => hash('sha256', 'downloaded_del_hash'),
            'luot_tai'      => 1,
            'trang_thai'    => 'DA_DUYET',
        ]);

        $response = $this->actingAs($author)->delete(route('documents.destroy', $document->getKey()), [
            'reason' => 'Muon xoa tai lieu',
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseHas('tai_lieu', [
            'ma_tai_lieu'   => $document->getKey(),
            'ngay_xoa_tam'  => null,
        ]);
    }

    // Quản trị viên quyết định xử lý tài liệu trong hàng đợi kiểm tra (KEEP, HIDE, RESTORE, DELETE)
    public function test_admin_decides_high_risk_document_review(): void
    {
        $admin = NguoiDung::factory()->admin()->create();
        $course = MonHoc::factory()->create();

        $document = TaiLieu::create([
            'ma_mon_hoc'    => $course->ma_mon_hoc,
            'ma_nguoi_dang' => NguoiDung::factory()->create()->getKey(),
            'tieu_de'       => 'Tai lieu cho kiem tra',
            'duong_dan_tep' => 'documents/flagged.pdf',
            'dinh_dang_tep' => 'pdf',
            'dung_luong_kb' => 100,
            'ma_bam_file'   => hash('sha256', 'flagged_hash'),
            'luot_tai'      => 2,
            'trang_thai'    => 'CHO_KIEM_TRA',
        ]);

        $response = $this->actingAs($admin)->put(route('admin.documents.decide', $document->getKey()), [
            'action'            => 'KEEP',
            'resolution_reason' => 'Tai lieu hoan toan hop le sau khi tham dinh thu cong.',
        ]);

        $response->assertSessionHas('success');
        $this->assertSame('DA_DUYET', $document->fresh()->trang_thai);
    }
}

