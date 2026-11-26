<?php

namespace Tests\Feature;

use App\Enums\TrangThaiNguoiDung;
use App\Enums\TrangThaiTaiLieu;
use App\Enums\VaiTroNguoiDung;
use App\Models\ChuyenNganh;
use App\Models\Khoa;
use App\Models\MonHoc;
use App\Models\NguoiDung;
use App\Models\TaiLieu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentAlumniIntegrationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected Khoa $faculty;
    protected ChuyenNganh $major;
    protected MonHoc $course;
    protected NguoiDung $student;
    protected NguoiDung $alumni;
    protected TaiLieu $document;

    protected function setUp(): void
    {
        parent::setUp();

        $this->faculty = Khoa::create([
            'ten_khoa'     => 'Khoa Kỹ thuật - Công nghệ - Môi trường',
            'ky_hieu_khoa' => 'KTCN',
        ]);

        $this->major = ChuyenNganh::create([
            'ma_khoa'          => $this->faculty->ma_khoa,
            'ten_chuyen_nganh' => 'Công nghệ Thông tin',
            'ky_hieu_nganh'    => 'DTH',
        ]);

        $this->course = MonHoc::create([
            'ma_khoa'     => $this->faculty->ma_khoa,
            'ma_hoc_phan' => 'COS201',
            'ten_mon_hoc' => 'Cơ sở Dữ liệu',
            'so_tin_chi'  => 3,
            'mo_ta'       => 'Môn học cơ sở ngành CNTT',
        ]);

        $this->student = NguoiDung::factory()->create([
            'ma_khoa'    => $this->faculty->ma_khoa,
            'vai_tro'    => VaiTroNguoiDung::SINH_VIEN,
            'trang_thai' => TrangThaiNguoiDung::DANG_HOAT_DONG,
            'ho_ten'     => 'Sinh Viên Test E2E',
            'mssv'       => 'DTH215001',
        ]);

        $this->alumni = NguoiDung::factory()->alumni()->create([
            'ma_khoa'    => $this->faculty->ma_khoa,
            'trang_thai' => TrangThaiNguoiDung::DANG_HOAT_DONG,
            'ho_ten'     => 'Cựu Sinh Viên Test E2E',
        ]);

        $this->document = TaiLieu::create([
            'ma_mon_hoc'    => $this->course->ma_mon_hoc,
            'ma_nguoi_dang' => $this->student->ma_nguoi_dung,
            'tieu_de'       => 'Đề thi cuối kỳ CSDL năm 2025',
            'mo_ta'         => 'Đề thi trắc nghiệm và thực hành SQL',
            'duong_dan_tep' => 'documents/csdl_2025.pdf',
            'dinh_dang_tep' => 'pdf',
            'dung_luong_kb' => 1024,
            'ma_bam_file'   => hash('sha256', 'e2e_csdl_document_content'),
            'phan_loai'     => 'DE_THI',
            'trang_thai'    => TrangThaiTaiLieu::DA_DUYET,
            'luot_xem'      => 10,
            'luot_tai'      => 5,
            'luot_thich'    => 2,
        ]);
    }

    public function test_guest_browsing_and_route_guards(): void
    {
        // 1. Khách truy cập trang chủ Welcome thành công
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Welcome'));

        // 2. Khách xem danh mục Khoa công khai
        $this->get(route('faculties.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Faculties/Index'));

        // 3. Khách bị chặn khi truy cập trang yêu cầu đăng nhập
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('wallet.index'))->assertRedirect(route('login'));
        $this->get(route('documents.create'))->assertRedirect(route('login'));
        $this->get(route('profile.edit'))->assertRedirect(route('login'));
    }

    public function test_student_complete_navigation_and_page_rendering(): void
    {
        // 1. Dashboard
        $this->actingAs($this->student)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard')
                ->has('stats')
                ->has('recentDocuments')
                ->has('faculties')
            );

        // 2. Danh mục Khoa
        $this->actingAs($this->student)
            ->get(route('faculties.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Faculties/Index')
                ->has('faculties')
            );

        // 3. Môn học
        $this->actingAs($this->student)
            ->get(route('courses.show', $this->course->ma_hoc_phan))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Courses/Show')
                ->has('course')
            );

        // 4. Chi tiết Tài liệu
        $this->actingAs($this->student)
            ->get(route('documents.show', $this->document->ma_tai_lieu))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Documents/Show')
                ->has('document')
            );

        // 5. Trang Đăng tải Tài liệu
        $this->actingAs($this->student)
            ->get(route('documents.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Documents/Create')
                ->has('faculties')
            );

        // 6. Ví điểm cá nhân
        $this->actingAs($this->student)
            ->get(route('wallet.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Wallet/Index')
                ->has('wallet')
                ->has('transactions')
                ->has('filters')
            );

        // 7. Thị trường Săn thưởng Bounty (Wishlist)
        $this->actingAs($this->student)
            ->get(route('document-requests.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Wishlist/Index')
            );

        // 8. Đánh giá môn học
        $this->actingAs($this->student)
            ->get(route('courses.reviews.index', $this->course->ma_hoc_phan))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Reviews/CourseReviews')
                ->has('mon_hoc')
                ->has('thong_ke')
                ->has('reviews')
            );

        // 9. Trang Hồ sơ cá nhân
        $this->actingAs($this->student)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Profile/Edit')
            );
    }

    public function test_alumni_has_equivalent_student_access(): void
    {
        // Cựu sinh viên đã kích hoạt có thể truy cập đầy đủ các module
        $this->actingAs($this->alumni)
            ->get(route('dashboard'))
            ->assertOk();

        $this->actingAs($this->alumni)
            ->get(route('documents.create'))
            ->assertOk();

        $this->actingAs($this->alumni)
            ->get(route('wallet.index'))
            ->assertOk();

        $this->actingAs($this->alumni)
            ->get(route('document-requests.index'))
            ->assertOk();
    }

    public function test_student_cannot_access_administrative_boundaries(): void
    {
        // Chặn sinh viên truy cập route quản trị viên
        $this->actingAs($this->student)
            ->get(route('admin.dashboard'))
            ->assertForbidden();

        $this->actingAs($this->student)
            ->get(route('admin.users.index'))
            ->assertForbidden();

        $this->actingAs($this->student)
            ->get(route('admin.kyc.index'))
            ->assertForbidden();

        $this->actingAs($this->student)
            ->get(route('admin.reports.index'))
            ->assertForbidden();
    }
}
