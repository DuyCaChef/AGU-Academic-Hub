<?php

namespace Tests\Feature;

use App\Models\Khoa;
use App\Models\MonHoc;
use App\Models\NguoiDung;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class Phase3aCourseDetailTest extends TestCase
{
    use RefreshDatabase;

    // AC-01: Truy cập /mon-hoc/{course_code} trả về 200 và render Courses/Show
    public function test_guest_can_access_course_detail_and_renders_courses_show(): void
    {
        $faculty = Khoa::factory()->create(['ten_khoa' => 'Khoa Công Nghệ Thông Tin', 'ky_hieu_khoa' => 'CNTT']);
        $course = MonHoc::factory()->create([
            'ma_khoa' => $faculty->ma_khoa,
            'ma_hoc_phan' => 'COS101',
            'ten_mon_hoc' => 'Nhập môn Lập trình',
            'so_tin_chi' => 3,
            'mo_ta' => 'Học phần cơ sở ngành CNTT.',
        ]);

        $response = $this->get(route('courses.show', 'COS101'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Courses/Show')
            ->where('course.ma_hoc_phan', 'COS101')
            ->where('course.ten_mon_hoc', 'Nhập môn Lập trình')
            ->where('course.so_tin_chi', 3)
            ->where('course.mo_ta', 'Học phần cơ sở ngành CNTT.')
            ->has('faculties')
        );
    }

    // AC-02, AC-04, AC-05: Hiển thị đầy đủ mã học phần, tên, tín chỉ, khoa và các chuyên ngành
    public function test_course_detail_provides_full_academic_metadata_with_faculty_and_majors(): void
    {
        $faculty = Khoa::factory()->create([
            'ten_khoa' => 'Khoa Kỹ thuật - Công nghệ - Môi trường',
            'ky_hieu_khoa' => 'KT-CN-MT',
        ]);
        $major1 = $faculty->chuyenNganhs()->create([
            'ky_hieu_nganh' => 'KTPM',
            'ten_chuyen_nganh' => 'Kỹ thuật Phần mềm',
        ]);
        $major2 = $faculty->chuyenNganhs()->create([
            'ky_hieu_nganh' => 'KHMT',
            'ten_chuyen_nganh' => 'Khoa học Máy tính',
        ]);

        $course = MonHoc::factory()->create([
            'ma_khoa' => $faculty->ma_khoa,
            'ma_hoc_phan' => 'SWE201',
            'ten_mon_hoc' => 'Kỹ nghệ Yêu cầu',
            'so_tin_chi' => 4,
            'mo_ta' => 'Thu thập, phân tích và quản lý yêu cầu phần mềm.',
        ]);
        $course->chuyenNganhs()->attach([$major1->ma_chuyen_nganh, $major2->ma_chuyen_nganh]);

        $response = $this->get(route('courses.show', 'SWE201'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Courses/Show')
            ->where('course.ma_hoc_phan', 'SWE201')
            ->where('course.ten_mon_hoc', 'Kỹ nghệ Yêu cầu')
            ->where('course.so_tin_chi', 4)
            ->where('course.khoa.ky_hieu_khoa', 'KT-CN-MT')
            ->where('course.khoa.ten_khoa', 'Khoa Kỹ thuật - Công nghệ - Môi trường')
            ->has('course.chuyen_nganhs', 2)
            ->where('course.chuyen_nganhs.0.ky_hieu_nganh', 'KTPM')
            ->where('course.chuyen_nganhs.1.ky_hieu_nganh', 'KHMT')
        );
    }

    // AC-03 & AC-06: Môn học không có mô tả và không gắn chuyên ngành (môn đại cương)
    public function test_course_without_majors_or_description_loads_successfully_without_runtime_error(): void
    {
        $faculty = Khoa::factory()->create(['ten_khoa' => 'Khoa Sư phạm', 'ky_hieu_khoa' => 'SP']);
        $course = MonHoc::factory()->create([
            'ma_khoa' => $faculty->ma_khoa,
            'ma_hoc_phan' => 'GEN101',
            'ten_mon_hoc' => 'Triết học Mác - Lênin',
            'so_tin_chi' => 3,
            'mo_ta' => null, // Không có mô tả
        ]);

        $response = $this->get(route('courses.show', 'GEN101'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Courses/Show')
            ->where('course.ma_hoc_phan', 'GEN101')
            ->where('course.mo_ta', null)
            ->has('course.chuyen_nganhs', 0)
        );
    }

    // AC-07: Mã học phần không tồn tại trả về HTTP 404
    public function test_non_existent_course_code_returns_404_not_found(): void
    {
        $response = $this->get(route('courses.show', 'NOT_EXIST_CODE_999'));
        $response->assertNotFound();
    }

    // AC-07 & AC-12: Môn học thuộc khoa bị soft delete trả về HTTP 404
    public function test_course_under_soft_deleted_faculty_returns_404(): void
    {
        $faculty = Khoa::factory()->create(['ky_hieu_khoa' => 'DEL_FAC']);
        $course = MonHoc::factory()->create([
            'ma_khoa' => $faculty->ma_khoa,
            'ma_hoc_phan' => 'DEL101',
        ]);

        $faculty->delete(); // Soft delete khoa

        $response = $this->get(route('courses.show', 'DEL101'));
        $response->assertNotFound();
    }

    // AC-09: Tra cứu linh hoạt bằng ID số nguyên (ma_mon_hoc)
    public function test_can_lookup_course_by_numeric_id_as_fallback(): void
    {
        $faculty = Khoa::factory()->create();
        $course = MonHoc::factory()->create([
            'ma_khoa' => $faculty->ma_khoa,
            'ma_hoc_phan' => 'PHY101',
            'ten_mon_hoc' => 'Vật lý đại cương',
        ]);

        $response = $this->get(route('courses.show', (string) $course->ma_mon_hoc));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Courses/Show')
            ->where('course.ma_hoc_phan', 'PHY101')
        );
    }

    // AC-10: Cả Sinh viên và Cựu sinh viên đều có quyền truy cập
    public function test_authenticated_student_and_alumni_can_access_course_detail(): void
    {
        $faculty = Khoa::factory()->create();
        $course = MonHoc::factory()->create([
            'ma_khoa' => $faculty->ma_khoa,
            'ma_hoc_phan' => 'CHE101',
        ]);

        $student = NguoiDung::factory()->create([
            'vai_tro' => 'SINH_VIEN',
        ]);
        $alumni = NguoiDung::factory()->create([
            'vai_tro' => 'CUU_SINH_VIEN',
        ]);

        // Student truy cập
        $this->actingAs($student)
            ->get(route('courses.show', 'CHE101'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Courses/Show')
                ->where('course.ma_hoc_phan', 'CHE101')
            );

        // Alumni truy cập
        $this->actingAs($alumni)
            ->get(route('courses.show', 'CHE101'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Courses/Show')
                ->where('course.ma_hoc_phan', 'CHE101')
            );
    }

    // AC-13: /mon-hoc/{course_code}/tai-lieu redirects to /mon-hoc/{course_code}
    public function test_course_documents_url_redirects_to_course_detail(): void
    {
        $faculty = Khoa::factory()->create();
        $course = MonHoc::factory()->create([
            'ma_khoa' => $faculty->ma_khoa,
            'ma_hoc_phan' => 'MTH101',
        ]);

        $response = $this->get(route('courses.documents', ['course_code' => 'MTH101', 'sort' => 'popular']));

        $response->assertRedirect(route('courses.show', ['course_code' => 'MTH101', 'sort' => 'popular']));
    }
}
