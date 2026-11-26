<?php

namespace Tests\Feature;

use App\Models\Khoa;
use App\Models\LichSuThungRac;
use App\Models\MonHoc;
use App\Models\NguoiDung;
use App\Models\TaiLieu;
use App\Models\YeuCauXacThuc;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminStorageManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_storage_dashboard(): void
    {
        $response = $this->get(route('admin.storage.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_regular_user_cannot_access_storage_dashboard(): void
    {
        $user = NguoiDung::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.storage.index'));
        $response->assertForbidden();
    }

    public function test_moderator_cannot_access_storage_dashboard(): void
    {
        $moderator = NguoiDung::factory()->moderator()->create();

        $response = $this->actingAs($moderator)->get(route('admin.storage.index'));
        $response->assertForbidden();
    }

    public function test_admin_can_view_storage_dashboard_with_all_metrics(): void
    {
        $admin = NguoiDung::factory()->admin()->create();

        $khoa = Khoa::create(['ten_khoa' => 'Công nghệ Thông tin', 'ky_hieu_khoa' => 'CNTT']);
        $monHoc = MonHoc::create([
            'ma_khoa' => $khoa->ma_khoa,
            'ma_hoc_phan' => 'COS101',
            'ten_mon_hoc' => 'Lập trình C++',
            'so_tin_chi' => 3,
        ]);

        $uploader = NguoiDung::factory()->create(['ma_khoa' => $khoa->ma_khoa]);

        // 1 active doc
        $doc1 = TaiLieu::create([
            'ma_mon_hoc' => $monHoc->ma_mon_hoc,
            'ma_nguoi_dang' => $uploader->ma_nguoi_dung,
            'tieu_de' => 'Bài giảng chương 1',
            'duong_dan_tep' => 'documents/bai_giang_1.pdf',
            'dinh_dang_tep' => 'pdf',
            'dung_luong_kb' => 15000,
            'ma_bam_file' => hash('sha256', 'content_unique'),
            'phan_loai' => 'BAI_GIANG',
            'trang_thai' => 'DA_DUYET',
        ]);

        // 1 duplicate doc
        $dupHash = hash('sha256', 'content_duplicate');
        $doc2 = TaiLieu::create([
            'ma_mon_hoc' => $monHoc->ma_mon_hoc,
            'ma_nguoi_dang' => $uploader->ma_nguoi_dung,
            'tieu_de' => 'Đồ án mẫu 1',
            'duong_dan_tep' => 'documents/do_an_1.pdf',
            'dinh_dang_tep' => 'pdf',
            'dung_luong_kb' => 25000, // >20MB => unusually large file
            'ma_bam_file' => $dupHash,
            'phan_loai' => 'DO_AN',
            'trang_thai' => 'DA_DUYET',
        ]);

        $doc3 = TaiLieu::create([
            'ma_mon_hoc' => $monHoc->ma_mon_hoc,
            'ma_nguoi_dang' => $uploader->ma_nguoi_dung,
            'tieu_de' => 'Bản sao Đồ án mẫu 1',
            'duong_dan_tep' => 'documents/do_an_1_copy.pdf',
            'dinh_dang_tep' => 'pdf',
            'dung_luong_kb' => 25000,
            'ma_bam_file' => $dupHash,
            'phan_loai' => 'DO_AN',
            'trang_thai' => 'DA_DUYET',
        ]);

        // 1 soft-deleted doc (expired)
        $doc4 = TaiLieu::create([
            'ma_mon_hoc' => $monHoc->ma_mon_hoc,
            'ma_nguoi_dang' => $uploader->ma_nguoi_dung,
            'tieu_de' => 'Đề thi cũ',
            'duong_dan_tep' => 'documents/de_thi_cu.pdf',
            'dinh_dang_tep' => 'pdf',
            'dung_luong_kb' => 8000,
            'ma_bam_file' => hash('sha256', 'content_de_thi'),
            'phan_loai' => 'DE_THI',
            'trang_thai' => 'DA_DUYET',
            'han_luu_thung_rac' => now()->subDay(),
        ]);
        $doc4->delete();

        // 1 KYC request
        YeuCauXacThuc::create([
            'ma_nguoi_dung' => $uploader->ma_nguoi_dung,
            'loai_xac_thuc' => 'CUU_SINH_VIEN',
            'mssv' => 'DTH123456',
            'nam_tot_nghiep' => 2022,
            'duong_dan_minh_chung' => 'private/kyc/test_proof.jpg',
            'trang_thai' => 'CHO_DUYET',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.storage.index'));

        $response->assertOk();
        $response->assertInertia(fn(Assert $page) => $page
            ->component('Admin/Storage/Index')
            ->has('totalStats')
            ->has('facultyStats')
            ->has('courseStats')
            ->has('typeStats')
            ->has('uploaderStats')
            ->has('trashStats')
            ->has('kycStats')
            ->has('largeFiles')
            ->has('duplicates')
            ->has('reclaimable')
            ->has('alert')
            ->has('growth')
            ->where('totalStats.chi_tiet.tai_lieu_hoat_dong.so_luong', 3)
            ->where('totalStats.chi_tiet.tai_lieu_xoa_mem.so_luong', 1)
            ->where('duplicates.tong_so_cum_trung_lap', 1)
            ->where('duplicates.tong_so_ban_trung_thua', 1)
            ->where('largeFiles.tong_so_tep_lon', 2)
            ->where('trashStats.da_het_han.so_luong', 1)
        );
    }

    public function test_admin_can_filter_courses_and_uploaders_by_search(): void
    {
        $admin = NguoiDung::factory()->admin()->create();

        $khoa = Khoa::create(['ten_khoa' => 'Kinh tế', 'ky_hieu_khoa' => 'KT']);
        $mon1 = MonHoc::create(['ma_khoa' => $khoa->ma_khoa, 'ma_hoc_phan' => 'KTE101', 'ten_mon_hoc' => 'Kinh tế vi mô']);
        $mon2 = MonHoc::create(['ma_khoa' => $khoa->ma_khoa, 'ma_hoc_phan' => 'MAT201', 'ten_mon_hoc' => 'Toán cao cấp']);

        $user1 = NguoiDung::factory()->create(['ho_ten' => 'Trần Văn Bình', 'email' => 'binh@agu.edu.vn']);
        $user2 = NguoiDung::factory()->create(['ho_ten' => 'Lê Thị Cúc', 'email' => 'cuc@agu.edu.vn']);

        TaiLieu::create([
            'ma_mon_hoc' => $mon1->ma_mon_hoc,
            'ma_nguoi_dang' => $user1->ma_nguoi_dung,
            'tieu_de' => 'Tài liệu vi mô',
            'duong_dan_tep' => 'doc1.pdf',
            'dinh_dang_tep' => 'pdf',
            'dung_luong_kb' => 5000,
            'ma_bam_file' => 'hash_a',
        ]);

        TaiLieu::create([
            'ma_mon_hoc' => $mon2->ma_mon_hoc,
            'ma_nguoi_dang' => $user2->ma_nguoi_dung,
            'tieu_de' => 'Tài liệu toán',
            'duong_dan_tep' => 'doc2.pdf',
            'dinh_dang_tep' => 'pdf',
            'dung_luong_kb' => 7000,
            'ma_bam_file' => 'hash_b',
        ]);

        // Search course
        $responseCourse = $this->actingAs($admin)->get(route('admin.storage.index', ['course_search' => 'KTE101']));
        $responseCourse->assertOk();
        $responseCourse->assertInertia(fn(Assert $page) => $page
            ->has('courseStats', 1)
            ->where('courseStats.0.ma_hoc_phan', 'KTE101')
        );

        // Search uploader
        $responseUploader = $this->actingAs($admin)->get(route('admin.storage.index', ['uploader_search' => 'binh@agu.edu.vn']));
        $responseUploader->assertOk();
        $responseUploader->assertInertia(fn(Assert $page) => $page
            ->has('uploaderStats', 1)
            ->where('uploaderStats.0.email', 'binh@agu.edu.vn')
        );
    }

    public function test_admin_can_filter_large_files_with_custom_threshold(): void
    {
        $admin = NguoiDung::factory()->admin()->create();

        $mon = MonHoc::factory()->create();
        $user = NguoiDung::factory()->create();

        // 15 MB file (15360 KB)
        TaiLieu::create([
            'ma_mon_hoc' => $mon->ma_mon_hoc,
            'ma_nguoi_dang' => $user->ma_nguoi_dung,
            'tieu_de' => 'File vừa 15MB',
            'duong_dan_tep' => 'doc15.pdf',
            'dinh_dang_tep' => 'pdf',
            'dung_luong_kb' => 15360,
            'ma_bam_file' => 'hash_15',
        ]);

        // 35 MB file (35840 KB)
        TaiLieu::create([
            'ma_mon_hoc' => $mon->ma_mon_hoc,
            'ma_nguoi_dang' => $user->ma_nguoi_dung,
            'tieu_de' => 'File lớn 35MB',
            'duong_dan_tep' => 'doc35.pdf',
            'dinh_dang_tep' => 'pdf',
            'dung_luong_kb' => 35840,
            'ma_bam_file' => 'hash_35',
        ]);

        // Lọc ngưỡng 30MB => chỉ thấy file 35MB
        $response = $this->actingAs($admin)->get(route('admin.storage.index', ['large_file_threshold_mb' => 30]));
        $response->assertOk();
        $response->assertInertia(fn(Assert $page) => $page
            ->where('largeFiles.tong_so_tep_lon', 1)
            ->where('largeFiles.danh_sach.0.tieu_de', 'File lớn 35MB')
        );

        // Lọc ngưỡng 10MB => thấy cả 2 file
        $response10 = $this->actingAs($admin)->get(route('admin.storage.index', ['large_file_threshold_mb' => 10]));
        $response10->assertOk();
        $response10->assertInertia(fn(Assert $page) => $page
            ->where('largeFiles.tong_so_tep_lon', 2)
        );
    }

    public function test_admin_can_purge_expired_trash(): void
    {
        Storage::fake('local');
        $admin = NguoiDung::factory()->admin()->create();

        $mon = MonHoc::factory()->create();
        $user = NguoiDung::factory()->create();

        // File hết hạn lưu thùng rác
        $filePathExpired = 'documents/expired_file.pdf';
        Storage::disk('local')->put($filePathExpired, 'dummy content');

        $expiredDoc = TaiLieu::create([
            'ma_mon_hoc' => $mon->ma_mon_hoc,
            'ma_nguoi_dang' => $user->ma_nguoi_dung,
            'tieu_de' => 'Tài liệu hết hạn',
            'duong_dan_tep' => $filePathExpired,
            'dinh_dang_tep' => 'pdf',
            'dung_luong_kb' => 12000,
            'ma_bam_file' => 'hash_expired',
            'han_luu_thung_rac' => now()->subDays(5),
        ]);
        $expiredDoc->delete();

        // File còn trong thời gian ân hạn
        $graceDoc = TaiLieu::create([
            'ma_mon_hoc' => $mon->ma_mon_hoc,
            'ma_nguoi_dang' => $user->ma_nguoi_dung,
            'tieu_de' => 'Tài liệu còn hạn',
            'duong_dan_tep' => 'documents/grace.pdf',
            'dinh_dang_tep' => 'pdf',
            'dung_luong_kb' => 6000,
            'ma_bam_file' => 'hash_grace',
            'han_luu_thung_rac' => now()->addDays(20),
        ]);
        $graceDoc->delete();

        $response = $this->actingAs($admin)->post(route('admin.storage.purge-expired-trash'));
        $response->assertSessionHas('success');

        // File hết hạn đã bị xóa vĩnh viễn khỏi CSDL và Storage
        $this->assertDatabaseMissing('tai_lieu', ['ma_tai_lieu' => $expiredDoc->getKey()]);
        Storage::disk('local')->assertMissing($filePathExpired);

        // File còn trong ân hạn vẫn được giữ lại trong thùng rác
        $this->assertDatabaseHas('tai_lieu', ['ma_tai_lieu' => $graceDoc->getKey()]);

        // Đã ghi vết kiểm toán
        $this->assertDatabaseHas('lich_su_thung_rac', [
            'ma_tai_lieu' => $expiredDoc->getKey(),
            'ma_nguoi_thuc_hien' => $admin->getKey(),
            'hanh_dong' => 'PERMANENT_DELETE',
            'nguon' => 'SYSTEM_PURGE',
        ]);
    }

    public function test_non_admin_cannot_purge_expired_trash(): void
    {
        $user = NguoiDung::factory()->create();

        $response = $this->actingAs($user)->post(route('admin.storage.purge-expired-trash'));
        $response->assertForbidden();
    }

    public function test_caching_strategy_and_on_demand_invalidation(): void
    {
        \Illuminate\Support\Facades\Cache::flush();
        $admin = NguoiDung::factory()->admin()->create();

        $this->assertFalse(\Illuminate\Support\Facades\Cache::has('admin_storage_overview'));

        // Lần đầu truy cập -> Lưu vào cache
        $this->actingAs($admin)->get(route('admin.storage.index'))->assertOk();
        $this->assertTrue(\Illuminate\Support\Facades\Cache::has('admin_storage_overview'));

        // Lần thứ 2 với refresh=1 -> Tự động xóa cache cũ và tính lại
        $response = $this->actingAs($admin)->get(route('admin.storage.index', ['refresh' => 1]));
        $response->assertOk();
        $this->assertTrue(\Illuminate\Support\Facades\Cache::has('admin_storage_overview'));
    }

    public function test_kyc_tab_does_not_leak_server_physical_paths(): void
    {
        $admin = NguoiDung::factory()->admin()->create();
        $user = NguoiDung::factory()->create();

        YeuCauXacThuc::create([
            'ma_nguoi_dung' => $user->ma_nguoi_dung,
            'loai_xac_thuc' => 'CUU_SINH_VIEN',
            'mssv' => 'DTH999888',
            'nam_tot_nghiep' => 2021,
            'duong_dan_minh_chung' => 'private/kyc/secret_student_card.jpg',
            'trang_thai' => 'CHO_DUYET',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.storage.index', ['refresh' => 1]));
        $response->assertOk();

        // Kiểm tra không có chuỗi secret_student_card.jpg bị rò rỉ ra JSON Props
        $content = $response->getContent();
        $this->assertStringNotContainsString('secret_student_card.jpg', $content);
        $this->assertStringNotContainsString('storage/app/private', $content);

        $response->assertInertia(fn(Assert $page) => $page
            ->has('kycStats')
            ->where('kycStats.tong_so_ho_so_csdl', 1)
            ->where('kycStats.khu_vuc_bao_mat', 'Thư mục Private (Chỉ truy cập qua Signed URL 30 phút)')
        );
    }
}

