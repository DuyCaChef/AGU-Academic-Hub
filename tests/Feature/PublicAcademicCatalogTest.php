<?php

namespace Tests\Feature;

use App\Models\Khoa;
use App\Models\MonHoc;
use App\Models\NguoiDung;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicAcademicCatalogTest extends TestCase
{
    use RefreshDatabase;

    // Kiểm tra danh sách Khoa công khai trên Dashboard
    public function test_public_faculties_list_on_dashboard(): void
    {
        $khoaA = Khoa::factory()->create(['ten_khoa' => 'Khoa Cong Nghe Thong Tin']);
        $khoaB = Khoa::factory()->create(['ten_khoa' => 'Khoa Kinh Te']);

        $user = NguoiDung::factory()->create([
            'ma_khoa' => $khoaB->ma_khoa,
        ]);

        $response = $this->actingAs($user)->get(route('faculties.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('faculties', 2)
            ->where('faculties.0.ma_khoa', $khoaB->ma_khoa)
        );
    }

    // Kiểm tra API lấy danh sách môn học theo Khoa
    public function test_get_courses_by_faculty_id_api(): void
    {
        $faculty = Khoa::factory()->create();
        $course1 = MonHoc::factory()->create(['ma_khoa' => $faculty->ma_khoa, 'ma_hoc_phan' => 'COS101']);
        $course2 = MonHoc::factory()->create(['ma_khoa' => $faculty->ma_khoa, 'ma_hoc_phan' => 'COS102']);

        $response = $this->getJson(route('faculties.courses', $faculty->ma_khoa));

        $response->assertOk();
        $response->assertJsonCount(2, 'courses');
        $response->assertJsonFragment(['ma_hoc_phan' => 'COS101']);
        $response->assertJsonFragment(['ma_hoc_phan' => 'COS102']);
    }

    // Kiểm tra trang tra cứu môn học công khai
    public function test_public_courses_index(): void
    {
        Khoa::factory()->create();

        $response = $this->get(route('courses.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Documents/Index')
            ->has('faculties')
            ->has('filters')
        );
    }

    // Kiểm tra xem chi tiết môn học công khai
    public function test_public_course_detail(): void
    {
        $faculty = Khoa::factory()->create();
        $course = MonHoc::factory()->create([
            'ma_khoa' => $faculty->ma_khoa,
            'ma_hoc_phan' => 'COS201',
        ]);

        $response = $this->get(route('courses.show', 'COS201'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Documents/Index')
            ->where('course.ma_hoc_phan', 'COS201')
        );
    }

    // Kiểm tra môn học thuộc Khoa bị vô hiệu hóa không xem được
    public function test_inactive_faculty_course_cannot_be_viewed(): void
    {
        $faculty = Khoa::factory()->create();
        $course = MonHoc::factory()->create([
            'ma_khoa' => $faculty->ma_khoa,
            'ma_hoc_phan' => 'COS301',
        ]);

        // Vô hiệu hóa khoa (soft delete)
        $faculty->delete();

        $response = $this->get(route('courses.show', 'COS301'));
        $response->assertNotFound();
    }

    // Kiểm tra khoa chưa có môn học vẫn trả về mảng rỗng không gây lỗi
    public function test_faculty_without_courses_returns_empty_mon_hocs(): void
    {
        $faculty = Khoa::factory()->create(['ten_khoa' => 'Khoa Luat']);

        $response = $this->getJson(route('faculties.courses', $faculty->ma_khoa));

        $response->assertOk();
        $response->assertJsonCount(0, 'courses');
    }

    // Kiểm tra môn học dùng chung giữa 2 chuyên ngành không bị nhân bản
    public function test_shared_course_between_majors_without_duplication(): void
    {
        $faculty = Khoa::factory()->create(['ten_khoa' => 'Khoa Cong Nghe Thong Tin']);
        $major1 = $faculty->chuyenNganhs()->create([
            'ky_hieu_nganh' => 'DTH',
            'ten_chuyen_nganh' => 'Tin Hoc Ung Dung',
        ]);
        $major2 = $faculty->chuyenNganhs()->create([
            'ky_hieu_nganh' => 'DPM',
            'ten_chuyen_nganh' => 'Ky Thuat Phan Mem',
        ]);

        // Môn Cơ sở dữ liệu dùng chung cho cả 2 chuyên ngành
        $sharedCourse = MonHoc::factory()->create([
            'ma_khoa' => $faculty->ma_khoa,
            'ma_hoc_phan' => 'COS205',
            'ten_mon_hoc' => 'Co So Du Lieu',
        ]);
        $sharedCourse->chuyenNganhs()->attach([$major1->ma_chuyen_nganh, $major2->ma_chuyen_nganh]);

        // Môn chuyên biệt cho DTH
        $specificCourse = MonHoc::factory()->create([
            'ma_khoa' => $faculty->ma_khoa,
            'ma_hoc_phan' => 'COS305',
            'ten_mon_hoc' => 'Lap Trinh Mang',
        ]);
        $specificCourse->chuyenNganhs()->attach([$major1->ma_chuyen_nganh]);

        // API lấy môn học theo chuyên ngành DPM chỉ có 1 môn Cơ sở dữ liệu
        $responseDpm = $this->getJson(route('faculties.majors.courses', $major2->ma_chuyen_nganh));
        $responseDpm->assertOk();
        $responseDpm->assertJsonCount(1, 'courses');
        $responseDpm->assertJsonFragment(['ma_hoc_phan' => 'COS205']);

        // API lấy môn học theo chuyên ngành DTH có cả 2 môn và không trùng lặp
        $responseDth = $this->getJson(route('faculties.majors.courses', $major1->ma_chuyen_nganh));
        $responseDth->assertOk();
        $responseDth->assertJsonCount(2, 'courses');
        $responseDth->assertJsonFragment(['ma_hoc_phan' => 'COS205']);
        $responseDth->assertJsonFragment(['ma_hoc_phan' => 'COS305']);
    }

    // Kiểm tra môn học xóa mềm bị loại trừ khỏi danh sách công khai
    public function test_soft_deleted_courses_are_excluded(): void
    {
        $faculty = Khoa::factory()->create();
        $activeCourse = MonHoc::factory()->create([
            'ma_khoa' => $faculty->ma_khoa,
            'ma_hoc_phan' => 'COS111',
            'ten_mon_hoc' => 'Mon Dang Hoat Dong',
        ]);
        $deletedCourse = MonHoc::factory()->create([
            'ma_khoa' => $faculty->ma_khoa,
            'ma_hoc_phan' => 'COS999',
            'ten_mon_hoc' => 'Mon Da Xoa Tam',
        ]);
        $deletedCourse->delete();

        $response = $this->getJson(route('faculties.courses', $faculty->ma_khoa));

        $response->assertOk();
        $response->assertJsonCount(1, 'courses');
        $response->assertJsonFragment(['ma_hoc_phan' => 'COS111']);
        $response->assertJsonMissing(['ma_hoc_phan' => 'COS999']);
    }

    // Kiểm tra sinh viên xem trang tổng quan với tài liệu mới nhất không bị lỗi SQL cột
    public function test_authenticated_student_can_view_dashboard_with_recent_documents(): void
    {
        $faculty = Khoa::factory()->create();
        $user = NguoiDung::factory()->create(['ma_khoa' => $faculty->ma_khoa]);
        $course = MonHoc::factory()->create(['ma_khoa' => $faculty->ma_khoa]);

        \App\Models\TaiLieu::factory()->create([
            'ma_mon_hoc' => $course->ma_mon_hoc,
            'ma_nguoi_dang' => $user->ma_nguoi_dung,
            'trang_thai' => 'DA_DUYET',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('faculties')
            ->has('stats')
            ->has('recentDocuments', 1)
        );
    }

    // Kiểm tra chi tiết môn học nạp đúng danh sách tài liệu và cây khoa
    public function test_public_course_detail_provides_documents_and_faculties(): void
    {
        $faculty = Khoa::factory()->create();
        $user = NguoiDung::factory()->create();
        $course = MonHoc::factory()->create([
            'ma_khoa' => $faculty->ma_khoa,
            'ma_hoc_phan' => 'COS501',
        ]);

        \App\Models\TaiLieu::factory()->create([
            'ma_mon_hoc' => $course->ma_mon_hoc,
            'ma_nguoi_dang' => $user->ma_nguoi_dung,
            'trang_thai' => 'DA_DUYET',
        ]);

        // Tra cứu bằng mã học phần
        $responseByCode = $this->get(route('courses.show', 'COS501'));
        $responseByCode->assertOk();
        $responseByCode->assertInertia(fn (Assert $page) => $page
            ->component('Documents/Index')
            ->where('course.ma_hoc_phan', 'COS501')
            ->has('faculties')
            ->has('documents.data', 1)
        );

        // Tra cứu linh hoạt bằng ID số nguyên (phòng thủ khi client gọi ID)
        $responseById = $this->get(route('courses.show', (string) $course->ma_mon_hoc));
        $responseById->assertOk();
        $responseById->assertInertia(fn (Assert $page) => $page
            ->component('Documents/Index')
            ->where('course.ma_hoc_phan', 'COS501')
        );
    }

    // Kiểm tra chuyên ngành thuộc khoa bị vô hiệu hóa không trả về môn học
    public function test_courses_for_major_under_inactive_faculty_are_excluded(): void
    {
        $faculty = Khoa::factory()->create();
        $major = $faculty->chuyenNganhs()->create([
            'ky_hieu_nganh' => 'DAN',
            'ten_chuyen_nganh' => 'Nganh Thuoc Khoa An',
        ]);
        $course = MonHoc::factory()->create(['ma_khoa' => $faculty->ma_khoa]);
        $course->chuyenNganhs()->attach($major->ma_chuyen_nganh);

        // Vô hiệu hóa khoa cha
        $faculty->delete();

        $response = $this->getJson(route('faculties.majors.courses', $major->ma_chuyen_nganh));
        $response->assertOk();
        $response->assertJsonCount(0, 'courses');
    }
}

