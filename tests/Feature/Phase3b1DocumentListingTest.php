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

class Phase3b1DocumentListingTest extends TestCase
{
    use RefreshDatabase;

    // AC-01 & AC-04: Trang chi tiết môn học hiển thị kho tài liệu kèm đúng metadata
    public function test_course_detail_shows_approved_documents_with_full_metadata(): void
    {
        $faculty = Khoa::factory()->create(['ky_hieu_khoa' => 'CNTT']);
        $course = MonHoc::factory()->create([
            'ma_khoa' => $faculty->ma_khoa,
            'ma_hoc_phan' => 'COS201',
            'ten_mon_hoc' => 'Cấu trúc dữ liệu và giải thuật',
        ]);

        $uploader = NguoiDung::factory()->create([
            'ho_ten' => 'Nguyễn Văn Uploader',
            'vai_tro' => 'SINH_VIEN',
        ]);

        $doc1 = TaiLieu::factory()->create([
            'ma_mon_hoc' => $course->ma_mon_hoc,
            'ma_nguoi_dang' => $uploader->ma_nguoi_dung,
            'tieu_de' => 'Đề thi cuối kỳ CTDL 2024',
            'phan_loai' => 'DE_THI',
            'phien_ban' => '1.0',
            'dinh_dang_tep' => 'pdf',
            'dung_luong_kb' => 1500,
            'luot_tai' => 25,
            'luot_thich' => 12,
            'trang_thai' => 'DA_DUYET',
        ]);

        $response = $this->get(route('courses.show', 'COS201'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Courses/Show')
            ->where('course.ma_hoc_phan', 'COS201')
            ->has('documents.data', 1)
            ->where('documents.data.0.ma_tai_lieu', $doc1->ma_tai_lieu)
            ->where('documents.data.0.tieu_de', 'Đề thi cuối kỳ CTDL 2024')
            ->where('documents.data.0.phan_loai', 'DE_THI')
            ->where('documents.data.0.luot_tai', 25)
            ->where('documents.data.0.luot_thich', 12)
            ->where('documents.data.0.nguoi_dang.ho_ten', 'Nguyễn Văn Uploader')
            ->where('documents.total', 1)
            ->has('filters')
        );
    }

    // AC-02 & AC-03: Tài liệu thuộc nhiều môn học khác nhau không bị lẫn lộn
    public function test_documents_from_different_courses_are_strictly_isolated(): void
    {
        $faculty = Khoa::factory()->create();
        $courseA = MonHoc::factory()->create([
            'ma_khoa' => $faculty->ma_khoa,
            'ma_hoc_phan' => 'COS202',
            'ten_mon_hoc' => 'Lập trình Hướng đối tượng',
        ]);
        $courseB = MonHoc::factory()->create([
            'ma_khoa' => $faculty->ma_khoa,
            'ma_hoc_phan' => 'MAT101',
            'ten_mon_hoc' => 'Giải tích 1',
        ]);

        $uploader = NguoiDung::factory()->create();

        // Tài liệu thuộc Course A
        $docA = TaiLieu::factory()->create([
            'ma_mon_hoc' => $courseA->ma_mon_hoc,
            'ma_nguoi_dang' => $uploader->ma_nguoi_dung,
            'tieu_de' => 'Bài giảng OOP Java',
            'trang_thai' => 'DA_DUYET',
        ]);

        // Tài liệu thuộc Course B
        $docB = TaiLieu::factory()->create([
            'ma_mon_hoc' => $courseB->ma_mon_hoc,
            'ma_nguoi_dang' => $uploader->ma_nguoi_dung,
            'tieu_de' => 'Đề thi Vi tích phân',
            'trang_thai' => 'DA_DUYET',
        ]);

        // Truy cập Course A: chỉ thấy docA, tuyệt đối không thấy docB
        $responseA = $this->get(route('courses.show', 'COS202', absolute: false));
        $responseA->assertOk();
        $responseA->assertInertia(fn (Assert $page) => $page
            ->component('Courses/Show')
            ->where('course.ma_hoc_phan', 'COS202')
            ->has('documents.data', 1)
            ->where('documents.data.0.ma_tai_lieu', $docA->ma_tai_lieu)
            ->where('documents.total', 1)
        );

        // Truy cập Course B: chỉ thấy docB, tuyệt đối không thấy docA
        $responseB = $this->get(route('courses.show', 'MAT101', absolute: false));
        $responseB->assertOk();
        $responseB->assertInertia(fn (Assert $page) => $page
            ->component('Courses/Show')
            ->where('course.ma_hoc_phan', 'MAT101')
            ->has('documents.data', 1)
            ->where('documents.data.0.ma_tai_lieu', $docB->ma_tai_lieu)
            ->where('documents.total', 1)
        );
    }

    // AC-05: Môn học không có tài liệu hiển thị danh sách rỗng (Empty State)
    public function test_course_without_documents_returns_empty_collection(): void
    {
        $faculty = Khoa::factory()->create();
        $course = MonHoc::factory()->create([
            'ma_khoa' => $faculty->ma_khoa,
            'ma_hoc_phan' => 'EMPTY101',
        ]);

        $response = $this->get(route('courses.show', 'EMPTY101'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Courses/Show')
            ->where('course.ma_hoc_phan', 'EMPTY101')
            ->has('documents.data', 0)
            ->where('documents.total', 0)
        );
    }

    // AC-13: Tài liệu chưa duyệt hoặc bị xóa tạm không được hiển thị (Bảo mật công khai)
    public function test_unapproved_or_soft_deleted_documents_are_never_leaked(): void
    {
        $faculty = Khoa::factory()->create();
        $course = MonHoc::factory()->create([
            'ma_khoa' => $faculty->ma_khoa,
            'ma_hoc_phan' => 'SEC101',
        ]);

        $uploader = NguoiDung::factory()->create();

        // 1. Tài liệu chờ duyệt
        TaiLieu::factory()->create([
            'ma_mon_hoc' => $course->ma_mon_hoc,
            'ma_nguoi_dang' => $uploader->ma_nguoi_dung,
            'tieu_de' => 'Tài liệu chờ kiểm duyệt',
            'trang_thai' => 'CHO_DUYET',
        ]);

        // 2. Tài liệu bị từ chối
        TaiLieu::factory()->create([
            'ma_mon_hoc' => $course->ma_mon_hoc,
            'ma_nguoi_dang' => $uploader->ma_nguoi_dung,
            'tieu_de' => 'Tài liệu bị từ chối',
            'trang_thai' => 'TU_CHOI',
        ]);

        // 3. Tài liệu đã duyệt nhưng bị xóa tạm (soft deleted)
        $deletedDoc = TaiLieu::factory()->create([
            'ma_mon_hoc' => $course->ma_mon_hoc,
            'ma_nguoi_dang' => $uploader->ma_nguoi_dung,
            'tieu_de' => 'Tài liệu đã xóa vào thùng rác',
            'trang_thai' => 'DA_DUYET',
        ]);
        $deletedDoc->delete();

        // 4. Tài liệu hợp lệ duy nhất
        $validDoc = TaiLieu::factory()->create([
            'ma_mon_hoc' => $course->ma_mon_hoc,
            'ma_nguoi_dang' => $uploader->ma_nguoi_dung,
            'tieu_de' => 'Tài liệu hợp lệ duy nhất',
            'trang_thai' => 'DA_DUYET',
        ]);

        $response = $this->get(route('courses.show', 'SEC101'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Courses/Show')
            ->where('course.ma_hoc_phan', 'SEC101')
            ->has('documents.data', 1)
            ->where('documents.data.0.ma_tai_lieu', $validDoc->ma_tai_lieu)
            ->where('documents.total', 1)
        );
    }

    // AC-06 & AC-07: Lọc theo từ khóa, chuyên mục và phân trang
    public function test_document_filtering_and_pagination_on_course_detail(): void
    {
        $faculty = Khoa::factory()->create();
        $course = MonHoc::factory()->create([
            'ma_khoa' => $faculty->ma_khoa,
            'ma_hoc_phan' => 'FIL101',
        ]);

        $uploader = NguoiDung::factory()->create();

        // Tạo 10 tài liệu loại DE_THI
        for ($i = 1; $i <= 10; $i++) {
            TaiLieu::factory()->create([
                'ma_mon_hoc' => $course->ma_mon_hoc,
                'ma_nguoi_dang' => $uploader->ma_nguoi_dung,
                'tieu_de' => "Đề thi số {$i}",
                'phan_loai' => 'DE_THI',
                'trang_thai' => 'DA_DUYET',
            ]);
        }

        // Tạo 1 tài liệu loại BAI_GIANG với tiêu đề đặc biệt
        $slide = TaiLieu::factory()->create([
            'ma_mon_hoc' => $course->ma_mon_hoc,
            'ma_nguoi_dang' => $uploader->ma_nguoi_dung,
            'tieu_de' => 'Slide bài giảng đặc biệt',
            'phan_loai' => 'BAI_GIANG',
            'trang_thai' => 'DA_DUYET',
        ]);

        // Lọc theo search: 'đặc biệt'
        $searchResponse = $this->get(route('courses.show', ['course_code' => 'FIL101', 'search' => 'đặc biệt']));
        $searchResponse->assertOk();
        $searchResponse->assertInertia(fn (Assert $page) => $page
            ->component('Courses/Show')
            ->has('documents.data', 1)
            ->where('documents.data.0.ma_tai_lieu', $slide->ma_tai_lieu)
            ->where('documents.total', 1)
        );

        // Lọc theo category: 'DE_THI' (tổng 10 bản ghi, trang 1 có 8 bản ghi do perPage = 8)
        $catResponse = $this->get(route('courses.show', ['course_code' => 'FIL101', 'category' => 'DE_THI']));
        $catResponse->assertOk();
        $catResponse->assertInertia(fn (Assert $page) => $page
            ->component('Courses/Show')
            ->has('documents.data', 8)
            ->where('documents.total', 10)
            ->where('documents.last_page', 2)
        );

        // Chuyển sang trang 2 của category 'DE_THI'
        $page2Response = $this->get(route('courses.show', ['course_code' => 'FIL101', 'category' => 'DE_THI', 'page' => 2]));
        $page2Response->assertOk();
        $page2Response->assertInertia(fn (Assert $page) => $page
            ->component('Courses/Show')
            ->has('documents.data', 2)
            ->where('documents.current_page', 2)
        );
    }

    // AC-12: Sinh viên và Cựu sinh viên đều truy cập đúng chính sách
    public function test_student_and_alumni_can_view_course_documents(): void
    {
        $faculty = Khoa::factory()->create();
        $course = MonHoc::factory()->create([
            'ma_khoa' => $faculty->ma_khoa,
            'ma_hoc_phan' => 'ROLE101',
        ]);

        $uploader = NguoiDung::factory()->create();
        TaiLieu::factory()->create([
            'ma_mon_hoc' => $course->ma_mon_hoc,
            'ma_nguoi_dang' => $uploader->ma_nguoi_dung,
            'tieu_de' => 'Tài liệu mẫu Role Test',
            'trang_thai' => 'DA_DUYET',
        ]);

        $student = NguoiDung::factory()->create(['vai_tro' => 'SINH_VIEN']);
        $alumni = NguoiDung::factory()->create(['vai_tro' => 'CUU_SINH_VIEN']);

        // Student truy cập
        $this->actingAs($student)
            ->get(route('courses.show', 'ROLE101'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Courses/Show')
                ->has('documents.data', 1)
            );

        // Alumni truy cập
        $this->actingAs($alumni)
            ->get(route('courses.show', 'ROLE101'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Courses/Show')
                ->has('documents.data', 1)
            );
    }

    // AC-08, AC-09, AC-10, AC-11: Tương tác chi tiết, Preview, Download và Upvote hoạt động đúng
    public function test_document_detail_preview_download_and_upvote_workflows(): void
    {
        Storage::fake('local');
        $file = UploadedFile::fake()->create('sample_exam.pdf', 500, 'application/pdf');
        $storedPath = $file->store('documents/pdf', 'local');

        $faculty = Khoa::factory()->create();
        $course = MonHoc::factory()->create([
            'ma_khoa' => $faculty->ma_khoa,
            'ma_hoc_phan' => 'ACT101',
        ]);

        $student = NguoiDung::factory()->create(['vai_tro' => 'SINH_VIEN']);

        $document = TaiLieu::factory()->create([
            'ma_mon_hoc' => $course->ma_mon_hoc,
            'ma_nguoi_dang' => $student->ma_nguoi_dung,
            'tieu_de' => 'Tài liệu tích hợp các hành động',
            'duong_dan_tep' => $storedPath,
            'dinh_dang_tep' => 'pdf',
            'luot_tai' => 5,
            'luot_thich' => 3,
            'trang_thai' => 'DA_DUYET',
        ]);

        // AC-08: Mở chi tiết tài liệu (documents.show)
        $this->actingAs($student)
            ->get(route('documents.show', $document->ma_tai_lieu))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Documents/Show')
                ->where('document.ma_tai_lieu', $document->ma_tai_lieu)
            );

        // AC-09: Preview PDF (documents.preview)
        $this->actingAs($student)
            ->get(route('documents.preview', $document->ma_tai_lieu))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        // AC-10: Download tài liệu (documents.download) - tự động tăng lượt tải
        $this->actingAs($student)
            ->get(route('documents.download', $document->ma_tai_lieu))
            ->assertOk();
        $this->assertEquals(6, $document->fresh()->luot_tai);

        // AC-11: Tác giả không thể tự thích tài liệu của chính mình
        $this->actingAs($student)
            ->postJson(route('documents.upvote', $document->ma_tai_lieu))
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Bạn không thể tự thích tài liệu của chính mình.',
            ]);
        $this->assertEquals(3, $document->fresh()->luot_thich);

        // Sinh viên khác có thể bình chọn tài liệu (documents.upvote)
        $otherStudent = NguoiDung::factory()->create(['vai_tro' => 'SINH_VIEN']);
        $this->actingAs($otherStudent)
            ->postJson(route('documents.upvote', $document->ma_tai_lieu))
            ->assertOk()
            ->assertJson([
                'success'     => true,
                'has_upvoted' => true,
                'upvotes'     => 4,
            ]);
        $this->assertEquals(4, $document->fresh()->luot_thich);
    }
}

