<?php

namespace Tests\Feature;

use App\Models\Khoa;
use App\Models\MonHoc;
use App\Models\NguoiDung;
use App\Models\TaiLieu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class Phase2DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected NguoiDung $student;
    protected NguoiDung $alumni;
    protected Khoa $faculty;
    protected MonHoc $course;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Tạo Khoa & Môn học
        $this->faculty = Khoa::factory()->create([
            'ky_hieu_khoa' => 'CNTT',
            'ten_khoa'     => 'Khoa Công nghệ thông tin',
        ]);

        $this->course = MonHoc::factory()->create([
            'ma_khoa'     => $this->faculty->ma_khoa,
            'ma_hoc_phan' => 'COS101',
            'ten_mon_hoc' => 'Lập trình căn bản',
        ]);

        // 2. Tạo Sinh viên
        $this->student = NguoiDung::factory()->create([
            'vai_tro'     => 'SINH_VIEN',
            'ho_ten'      => 'Nguyen Van Sinh Vien',
            'mssv'        => 'DTH200001',
            'ma_khoa'     => $this->faculty->ma_khoa,
            'diem_uy_tin' => 120,
        ]);

        // 3. Tạo Cựu sinh viên (không có MSSV)
        $this->alumni = NguoiDung::factory()->create([
            'vai_tro'     => 'CUU_SINH_VIEN',
            'ho_ten'      => 'Tran Thi Cuu Sinh Vien',
            'mssv'        => null,
            'ma_khoa'     => null,
            'diem_uy_tin' => 250,
        ]);
    }

    // AC-01 & AC-04 & AC-05: Khi CSDL có tài liệu mới -> Dashboard hiển thị danh sách tài liệu
    public function test_ac01_ac04_dashboard_displays_recent_documents_when_available(): void
    {
        // Tạo 2 tài liệu đã duyệt với thời gian tạo khác biệt
        $doc1 = TaiLieu::factory()->create([
            'ma_mon_hoc'    => $this->course->ma_mon_hoc,
            'ma_nguoi_dang' => $this->student->ma_nguoi_dung,
            'tieu_de'       => 'De thi cuoi ky Lap trinh 2024',
            'trang_thai'    => 'DA_DUYET',
            'luot_tai'      => 15,
            'created_at'    => now()->subDay(),
        ]);

        $doc2 = TaiLieu::factory()->create([
            'ma_mon_hoc'    => $this->course->ma_mon_hoc,
            'ma_nguoi_dang' => $this->student->ma_nguoi_dung,
            'tieu_de'       => 'Slide bai giang chuong 1',
            'trang_thai'    => 'DA_DUYET',
            'luot_tai'      => 42,
            'created_at'    => now(),
        ]);

        $response = $this->actingAs($this->student)
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('recentDocuments', 2)
            ->where('recentDocuments.0.tieu_de', 'Slide bai giang chuong 1')
            ->where('recentDocuments.0.luot_tai', 42)
            ->where('recentDocuments.1.tieu_de', 'De thi cuoi ky Lap trinh 2024')
            ->where('recentDocuments.1.luot_tai', 15)
        );
    }

    // AC-02: Khi CSDL không có tài liệu đã duyệt -> recentDocuments là mảng rỗng
    public function test_ac02_dashboard_provides_empty_recent_documents_when_no_approved_documents(): void
    {
        // Tạo tài liệu nhưng trạng thái CHUA_DUYET (không được lên Dashboard)
        TaiLieu::factory()->create([
            'ma_mon_hoc'    => $this->course->ma_mon_hoc,
            'ma_nguoi_dang' => $this->student->ma_nguoi_dung,
            'tieu_de'       => 'Tai lieu chua duyet',
            'trang_thai'    => 'CHUA_DUYET',
        ]);

        $response = $this->actingAs($this->student)
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('recentDocuments', 0)
        );
    }

    // AC-05: Tải tài liệu hoạt động đúng thông qua route documents.download
    public function test_ac05_document_download_route_operates_correctly(): void
    {
        Storage::fake('local');
        $fakeFile = UploadedFile::fake()->create('tailieu.pdf', 100, 'application/pdf');
        $storedPath = $fakeFile->store('documents');

        $doc = TaiLieu::factory()->create([
            'ma_mon_hoc'    => $this->course->ma_mon_hoc,
            'ma_nguoi_dang' => $this->student->ma_nguoi_dung,
            'tieu_de'       => 'Tai lieu PDF Test Download',
            'duong_dan_tep' => $storedPath,
            'dinh_dang_tep' => 'pdf',
            'trang_thai'    => 'DA_DUYET',
            'luot_tai'      => 5,
        ]);

        // Thực hiện tải file
        $response = $this->actingAs($this->student)
            ->get(route('documents.download', $doc->ma_tai_lieu));

        $response->assertOk();

        // Kiểm tra biến đếm lượt tải tự động tăng
        $doc->refresh();
        $this->assertEquals(6, $doc->luot_tai);
    }

    // AC-06, AC-07, AC-08, AC-09: Kiểm tra 4 route đích của Tác vụ nhanh
    public function test_ac06_to_ac09_all_four_quick_link_routes_exist_and_accessible(): void
    {
        // 1. Thẻ 1: Tìm môn học -> courses.index
        $this->actingAs($this->student)
            ->get(route('courses.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Courses/Index'));

        // 2. Thẻ 2: Săn thưởng tài liệu -> document-requests.index
        $this->actingAs($this->student)
            ->get(route('document-requests.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Wishlist/Index'));

        // 3. Thẻ 3: Duyệt danh sách khoa -> faculties.index
        $this->actingAs($this->student)
            ->get(route('faculties.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Faculties/Index'));

        // 4. Thẻ 4: Đăng tài liệu -> documents.create
        $this->actingAs($this->student)
            ->get(route('documents.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Documents/Create'));
    }

    // AC-10: Cả Student và Alumni đều truy cập Dashboard đúng và nhận prop người dùng tương ứng
    public function test_ac10_student_and_alumni_access_dashboard_with_role_identity(): void
    {
        // 1. Sinh viên truy cập
        $responseStudent = $this->actingAs($this->student)
            ->get(route('dashboard'));

        $responseStudent->assertOk();
        $responseStudent->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('auth.user.vai_tro', 'SINH_VIEN')
            ->where('auth.user.mssv', 'DTH200001')
            ->has('stats')
        );

        // 2. Cựu sinh viên truy cập
        $responseAlumni = $this->actingAs($this->alumni)
            ->get(route('dashboard'));

        $responseAlumni->assertOk();
        $responseAlumni->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('auth.user.vai_tro', 'CUU_SINH_VIEN')
            ->where('auth.user.mssv', null)
            ->has('stats')
        );
    }

    // AC-13: Khách chưa đăng nhập bị chuyển hướng về login
    public function test_ac13_guest_redirected_to_login(): void
    {
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }
}
