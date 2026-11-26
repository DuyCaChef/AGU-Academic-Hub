<?php

namespace Tests\Feature;

use App\Models\DanhGiaMonHoc;
use App\Models\Khoa;
use App\Models\MonHoc;
use App\Models\NguoiDung;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_course_and_assign_a_major(): void
    {
        $admin = NguoiDung::factory()->admin()->create();
        $faculty = Khoa::factory()->create();
        $major = $faculty->chuyenNganhs()->create([
            'ky_hieu_nganh' => 'DTH',
            'ten_chuyen_nganh' => 'Dai hoc Tin hoc',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.catalog.courses.store'), [
            'ma_khoa' => $faculty->ma_khoa,
            'ma_hoc_phan' => 'COS101',
            'ten_mon_hoc' => 'Nhap mon Lap trinh',
            'so_tin_chi' => 3,
            'ma_chuyen_nganh' => [$major->ma_chuyen_nganh],
        ]);

        $response->assertSessionHasNoErrors();
        $course = MonHoc::where('ma_hoc_phan', 'COS101')->firstOrFail();
        $this->assertTrue($course->chuyenNganhs->contains($major));
    }

    public function test_duplicate_course_code_is_rejected(): void
    {
        $admin = NguoiDung::factory()->admin()->create();
        $faculty = Khoa::factory()->create();
        MonHoc::factory()->create(['ma_khoa' => $faculty->ma_khoa, 'ma_hoc_phan' => 'COS101']);

        $response = $this->actingAs($admin)->from(route('admin.catalog.index'))
            ->post(route('admin.catalog.courses.store'), [
                'ma_khoa' => $faculty->ma_khoa,
                'ma_hoc_phan' => 'COS101',
                'ten_mon_hoc' => 'Mon trung ma',
                'so_tin_chi' => 3,
            ]);

        $response->assertSessionHasErrors('ma_hoc_phan');
    }

    public function test_course_code_is_normalized_and_requires_three_letters_followed_by_three_digits(): void
    {
        $admin = NguoiDung::factory()->admin()->create();
        $faculty = Khoa::factory()->create();

        $this->actingAs($admin)->post(route('admin.catalog.courses.store'), [
            'ma_khoa' => $faculty->ma_khoa,
            'ma_hoc_phan' => ' pkd101 ',
            'ten_mon_hoc' => 'Phat trien ky nang',
            'so_tin_chi' => 3,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('mon_hoc', ['ma_hoc_phan' => 'PKD101']);

        foreach (['PK101', 'PKDD01', 'PKD1001', 'PKD-01', 'PKD10A'] as $invalidCode) {
            $response = $this->actingAs($admin)->post(route('admin.catalog.courses.store'), [
                'ma_khoa' => $faculty->ma_khoa,
                'ma_hoc_phan' => $invalidCode,
                'ten_mon_hoc' => 'Ma khong hop le',
                'so_tin_chi' => 3,
            ]);

            $response->assertSessionHasErrors('ma_hoc_phan');
        }
    }

    public function test_course_credits_cannot_exceed_eight(): void
    {
        $admin = NguoiDung::factory()->admin()->create();
        $faculty = Khoa::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.catalog.courses.store'), [
            'ma_khoa' => $faculty->ma_khoa,
            'ma_hoc_phan' => 'PKD101',
            'ten_mon_hoc' => 'Mon vuot qua gioi han tin chi',
            'so_tin_chi' => 9,
        ]);

        $response->assertSessionHasErrors('so_tin_chi');
        $this->assertDatabaseMissing('mon_hoc', ['ma_hoc_phan' => 'PKD101']);
    }

    public function test_catalog_codes_are_normalized_before_storage(): void
    {
        $admin = NguoiDung::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.catalog.faculties.store'), [
            'ky_hieu_khoa' => ' cntt ',
            'ten_khoa' => 'Khoa Công nghệ thông tin',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('khoa', [
            'ky_hieu_khoa' => 'CNTT',
        ]);
    }

    public function test_major_code_is_normalized_to_uppercase_and_must_follow_d_two_letters_format(): void
    {
        $admin = NguoiDung::factory()->admin()->create();
        $faculty = Khoa::factory()->create();

        $this->actingAs($admin)->post(route('admin.catalog.majors.store'), [
            'ma_khoa' => $faculty->ma_khoa,
            'ky_hieu_nganh' => ' dmk ',
            'ten_chuyen_nganh' => 'Marketing',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('chuyen_nganh', [
            'ky_hieu_nganh' => 'DMK',
        ]);

        $invalidResponse = $this->actingAs($admin)->post(route('admin.catalog.majors.store'), [
            'ma_khoa' => $faculty->ma_khoa,
            'ky_hieu_nganh' => 'DQTKD',
            'ten_chuyen_nganh' => 'Ma khong hop le',
        ]);

        $invalidResponse->assertSessionHasErrors('ky_hieu_nganh');
    }

    public function test_course_rejects_a_major_from_another_faculty_instead_of_ignoring_it(): void
    {
        $admin = NguoiDung::factory()->admin()->create();
        $faculty = Khoa::factory()->create();
        $otherFaculty = Khoa::factory()->create();
        $major = $otherFaculty->chuyenNganhs()->create([
            'ky_hieu_nganh' => 'DTH',
            'ten_chuyen_nganh' => 'Dai hoc Tin hoc',
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.catalog.courses.store'), [
                'ma_khoa' => $faculty->ma_khoa,
                'ma_hoc_phan' => 'COS101',
                'ten_mon_hoc' => 'Nhap mon Lap trinh',
                'so_tin_chi' => 3,
                'ma_chuyen_nganh' => [$major->ma_chuyen_nganh],
            ]);

        $response->assertSessionHasErrors('ma_chuyen_nganh.0');
        $this->assertDatabaseMissing('mon_hoc', ['ma_hoc_phan' => 'COS101']);
    }

    public function test_course_with_a_review_cannot_be_disabled(): void
    {
        $admin = NguoiDung::factory()->admin()->create();
        $student = NguoiDung::factory()->create();
        $course = MonHoc::factory()->create();
        DanhGiaMonHoc::factory()->create([
            'ma_mon_hoc' => $course->ma_mon_hoc,
            'ma_nguoi_dung' => $student->ma_nguoi_dung,
        ]);

        $response = $this->actingAs($admin)->put(route('admin.catalog.courses.toggle', $course->ma_mon_hoc));

        $response->assertSessionHasErrors('course');
        $this->assertDatabaseHas('mon_hoc', ['ma_mon_hoc' => $course->ma_mon_hoc, 'ngay_xoa_tam' => null]);
    }

    public function test_moderator_cannot_manage_academic_catalog(): void
    {
        $moderator = NguoiDung::factory()->moderator()->create();

        $this->actingAs($moderator)
            ->get(route('admin.catalog.index'))
            ->assertForbidden();
    }

    public function test_disabling_a_faculty_makes_children_effectively_inactive_without_soft_deleting_them(): void
    {
        $admin = NguoiDung::factory()->admin()->create();
        $faculty = Khoa::factory()->create();
        $major = $faculty->chuyenNganhs()->create([
            'ky_hieu_nganh' => 'DTH',
            'ten_chuyen_nganh' => 'Dai hoc Tin hoc',
        ]);
        $course = MonHoc::factory()->create(['ma_khoa' => $faculty->ma_khoa]);

        $this->actingAs($admin)
            ->put(route('admin.catalog.faculties.toggle', $faculty->ma_khoa))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('chuyen_nganh', [
            'ma_chuyen_nganh' => $major->ma_chuyen_nganh,
            'ngay_xoa_tam' => null,
        ]);
        $this->assertDatabaseHas('mon_hoc', [
            'ma_mon_hoc' => $course->ma_mon_hoc,
            'ngay_xoa_tam' => null,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.catalog.index'))
            ->assertInertia(fn ($page) => $page
                ->where('majors.total', 0)
                ->where('courses.total', 0));
    }

    public function test_child_cannot_be_reactivated_while_its_faculty_is_inactive(): void
    {
        $admin = NguoiDung::factory()->admin()->create();
        $faculty = Khoa::factory()->create();
        $major = $faculty->chuyenNganhs()->create([
            'ky_hieu_nganh' => 'DTH',
            'ten_chuyen_nganh' => 'Dai hoc Tin hoc',
        ]);
        $major->delete();
        $faculty->delete();

        $this->actingAs($admin)
            ->put(route('admin.catalog.majors.toggle', $major->ma_chuyen_nganh))
            ->assertSessionHasErrors('major');
    }
}
