<?php

namespace Tests\Feature;

use App\Enums\VaiTroNguoiDung;
use App\Models\Khoa;
use App\Models\MonHoc;
use App\Models\NguoiDung;
use App\Models\DanhGiaMonHoc;
use App\Models\BinhChonDanhGia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseReviewTest extends TestCase
{
    use RefreshDatabase;

    protected NguoiDung $student;
    protected MonHoc $course;

    protected function setUp(): void
    {
        parent::setUp();

        $khoa = Khoa::create(['ten_khoa' => 'Khoa CNTT', 'ky_hieu_khoa' => 'CNTT']);
        $this->student = NguoiDung::factory()->create([
            'ma_khoa' => $khoa->ma_khoa,
            'vai_tro' => VaiTroNguoiDung::SINH_VIEN,
        ]);

        $this->course = MonHoc::create([
            'ma_khoa' => $khoa->ma_khoa,
            'ma_hoc_phan' => 'COS101',
            'ten_mon_hoc' => 'Nhập môn Lập trình',
            'so_tin_chi' => 3,
            'mo_ta' => 'Môn học cơ sở ngành',
        ]);
    }

    public function test_can_view_course_reviews_page(): void
    {
        $response = $this->actingAs($this->student)
            ->get("/mon-hoc/{$this->course->ma_hoc_phan}/danh-gia");

        $response->assertStatus(200);
    }

    public function test_can_get_course_reviews_json(): void
    {
        // Tạo sẵn 1 đánh giá mẫu
        DanhGiaMonHoc::create([
            'ma_mon_hoc' => $this->course->ma_mon_hoc,
            'ma_nguoi_dung' => $this->student->ma_nguoi_dung,
            'hoc_ky' => '2025-1',
            'diem_do_kho' => 3,
            'diem_thuc_tien' => 5,
            'diem_khoi_luong' => 4,
            'noi_dung' => 'Môn học rất hay và bổ ích cho sinh viên năm nhất.',
            'an_danh' => true,
        ]);

        $response = $this->actingAs($this->student)
            ->getJson("/mon-hoc/{$this->course->ma_hoc_phan}/danh-gia");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'mon_hoc',
            'thong_ke' => [
                'tong_so_danh_gia',
                'avg_do_kho',
                'avg_thuc_tien',
                'avg_ap_luc',
                'avg_khoi_luong',
                'diem_tong_quan',
            ],
            'reviews',
        ]);
    }

    public function test_can_store_course_review(): void
    {
        $response = $this->actingAs($this->student)
            ->post("/mon-hoc/{$this->course->ma_hoc_phan}/danh-gia", [
                'hoc_ky' => '2025-2',
                'diem_do_kho' => 4,
                'diem_thuc_tien' => 5,
                'diem_ap_luc_bai_tap' => 3,
                'nhan_xet' => 'Môn học đòi hỏi tự học nhiều nhưng giảng viên dạy tận tâm.',
                'la_an_danh' => true,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('danh_gia_mon_hoc', [
            'ma_mon_hoc' => $this->course->ma_mon_hoc,
            'ma_nguoi_dung' => $this->student->ma_nguoi_dung,
            'hoc_ky' => '2025-2',
            'diem_khoi_luong' => 3,
            'an_danh' => 1,
        ]);
    }

    public function test_blocks_review_with_profanity(): void
    {
        $response = $this->actingAs($this->student)
            ->post("/mon-hoc/{$this->course->ma_hoc_phan}/danh-gia", [
                'hoc_ky' => '2025-2',
                'diem_do_kho' => 4,
                'diem_thuc_tien' => 5,
                'diem_ap_luc_bai_tap' => 3,
                'nhan_xet' => 'Môn học này đm vcl thật sự khó hiểu.',
                'la_an_danh' => true,
            ]);

        $response->assertSessionHasErrors(['nhan_xet']);
        $this->assertDatabaseMissing('danh_gia_mon_hoc', [
            'ma_mon_hoc' => $this->course->ma_mon_hoc,
            'hoc_ky' => '2025-2',
        ]);
    }

    public function test_allows_review_with_valid_word_containing_substring_like_admin(): void
    {
        $response = $this->actingAs($this->student)
            ->post("/mon-hoc/{$this->course->ma_hoc_phan}/danh-gia", [
                'hoc_ky' => '2025-2',
                'diem_do_kho' => 4,
                'diem_thuc_tien' => 5,
                'diem_ap_luc_bai_tap' => 3,
                'nhan_xet' => 'Cảm ơn admin đã cập nhật đề cương môn học rất đầy đủ.',
                'la_an_danh' => true,
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        $this->assertDatabaseHas('danh_gia_mon_hoc', [
            'ma_mon_hoc' => $this->course->ma_mon_hoc,
            'hoc_ky' => '2025-2',
            'noi_dung' => 'Cảm ơn admin đã cập nhật đề cương môn học rất đầy đủ.',
        ]);
    }

    public function test_toggle_helpful_vote(): void
    {
        $otherStudent = NguoiDung::factory()->create([
            'ma_khoa' => $this->course->ma_khoa,
            'vai_tro' => VaiTroNguoiDung::SINH_VIEN,
        ]);

        $review = DanhGiaMonHoc::create([
            'ma_mon_hoc' => $this->course->ma_mon_hoc,
            'ma_nguoi_dung' => $this->student->ma_nguoi_dung,
            'hoc_ky' => '2025-1',
            'diem_do_kho' => 3,
            'diem_thuc_tien' => 5,
            'diem_khoi_luong' => 4,
            'noi_dung' => 'Môn học rất hay và bổ ích cho sinh viên năm nhất.',
            'an_danh' => true,
        ]);

        // SV khác vote hữu ích
        $response = $this->actingAs($otherStudent)
            ->post("/danh-gia/{$review->ma_danh_gia}/huu-ich");

        $response->assertRedirect();
        $this->assertDatabaseHas('binh_chon_danh_gia', [
            'ma_danh_gia' => $review->ma_danh_gia,
            'ma_nguoi_dung' => $otherStudent->ma_nguoi_dung,
            'huu_ich' => 1,
        ]);

        // Vote lần nữa thì bỏ vote
        $response2 = $this->actingAs($otherStudent)
            ->post("/danh-gia/{$review->ma_danh_gia}/huu-ich");

        $response2->assertRedirect();
        $this->assertDatabaseMissing('binh_chon_danh_gia', [
            'ma_danh_gia' => $review->ma_danh_gia,
            'ma_nguoi_dung' => $otherStudent->ma_nguoi_dung,
        ]);
    }
}
