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
}

