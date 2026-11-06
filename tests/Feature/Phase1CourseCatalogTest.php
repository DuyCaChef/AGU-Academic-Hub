<?php

namespace Tests\Feature;

use App\Models\ChuyenNganh;
use App\Models\Khoa;
use App\Models\MonHoc;
use App\Models\NguoiDung;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class Phase1CourseCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected Khoa $faculty1;
    protected Khoa $faculty2;
    protected ChuyenNganh $major1;
    protected ChuyenNganh $major2;
    protected ChuyenNganh $major3;
    protected MonHoc $course1;
    protected MonHoc $course2;
    protected MonHoc $courseGeneral;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Tạo Khoa
        $this->faculty1 = Khoa::factory()->create([
            'ky_hieu_khoa' => 'CNTT',
            'ten_khoa'     => 'Khoa Công nghệ thông tin',
        ]);

        $this->faculty2 = Khoa::factory()->create([
            'ky_hieu_khoa' => 'KT',
            'ten_khoa'     => 'Khoa Kinh tế',
        ]);

        // 2. Tạo Chuyên ngành
        $this->major1 = $this->faculty1->chuyenNganhs()->create([
            'ky_hieu_nganh'    => 'DTH',
            'ten_chuyen_nganh' => 'Công nghệ thông tin',
        ]);

        $this->major2 = $this->faculty1->chuyenNganhs()->create([
            'ky_hieu_nganh'    => 'DPM',
            'ten_chuyen_nganh' => 'Kỹ thuật phần mềm',
        ]);

        $this->major3 = $this->faculty2->chuyenNganhs()->create([
            'ky_hieu_nganh'    => 'QTKD',
            'ten_chuyen_nganh' => 'Quản trị kinh doanh',
        ]);

        // 3. Tạo Môn học
        // Môn 1: Thuộc Khoa CNTT, áp dụng cho Major 1 và Major 2
        $this->course1 = MonHoc::factory()->create([
            'ma_khoa'     => $this->faculty1->ma_khoa,
            'ma_hoc_phan' => 'COS101',
            'ten_mon_hoc' => 'Lập trình căn bản C',
            'so_tin_chi'  => 3,
        ]);
        $this->course1->chuyenNganhs()->attach([$this->major1->ma_chuyen_nganh, $this->major2->ma_chuyen_nganh]);

        // Môn 2: Thuộc Khoa KT, áp dụng cho Major 3
        $this->course2 = MonHoc::factory()->create([
            'ma_khoa'     => $this->faculty2->ma_khoa,
            'ma_hoc_phan' => 'KTE201',
            'ten_mon_hoc' => 'Kinh tế vi mô căn bản',
            'so_tin_chi'  => 2,
        ]);
        $this->course2->chuyenNganhs()->attach([$this->major3->ma_chuyen_nganh]);

        // Môn 3: Môn đại cương thuộc Khoa CNTT, KHÔNG thuộc bất kỳ chuyên ngành nào
        $this->courseGeneral = MonHoc::factory()->create([
            'ma_khoa'     => $this->faculty1->ma_khoa,
            'ma_hoc_phan' => 'GEN101',
            'ten_mon_hoc' => 'Pháp luật đại cương',
            'so_tin_chi'  => 2,
        ]);
    }

    // AC-01: GET /mon-hoc render đúng Courses/Index
    public function test_ac01_courses_index_renders_correct_inertia_component(): void
    {
        $response = $this->get(route('courses.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Courses/Index')
            ->has('faculties')
            ->has('courses.data')
            ->has('filters')
        );
    }

    // AC-02: Dữ liệu môn học lấy từ database thật và chứa đầy đủ metadata
    public function test_ac02_courses_index_displays_real_database_records(): void
    {
        $response = $this->get(route('courses.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Courses/Index')
            ->where('courses.total', 3)
            ->has('courses.data.0', fn (Assert $item) => $item
                ->has('ma_mon_hoc')
                ->has('ma_hoc_phan')
                ->has('ten_mon_hoc')
                ->has('so_tin_chi')
                ->has('khoa')
                ->has('chuyen_nganhs')
                ->etc()
            )
        );
    }

    // AC-03: Tìm kiếm theo mã học phần chính xác
    public function test_ac03_search_by_course_code(): void
    {
        $response = $this->get(route('courses.index', ['search' => 'COS101']));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Courses/Index')
            ->where('courses.total', 1)
            ->where('courses.data.0.ma_hoc_phan', 'COS101')
            ->where('courses.data.0.ten_mon_hoc', 'Lập trình căn bản C')
        );
    }

    // AC-04: Tìm kiếm theo tên môn học chính xác
    public function test_ac04_search_by_course_name(): void
    {
        $response = $this->get(route('courses.index', ['search' => 'Kinh tế vi mô']));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Courses/Index')
            ->where('courses.total', 1)
            ->where('courses.data.0.ma_hoc_phan', 'KTE201')
        );
    }

    // AC-05: Lọc theo Khoa chính xác
    public function test_ac05_filter_by_faculty(): void
    {
        // Lọc Khoa CNTT (faculty1): có course1 và courseGeneral
        $response = $this->get(route('courses.index', ['ma_khoa' => $this->faculty1->ma_khoa]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Courses/Index')
            ->where('courses.total', 2)
            ->where('courses.data.0.khoa.ma_khoa', $this->faculty1->ma_khoa)
            ->where('courses.data.1.khoa.ma_khoa', $this->faculty1->ma_khoa)
        );
    }

    // AC-06: Lọc theo Chuyên ngành chính xác (qua quan hệ nhiều-nhiều)
    public function test_ac06_filter_by_major(): void
    {
        // Lọc theo Major 2 (Kỹ thuật phần mềm): chỉ có course1
        $response = $this->get(route('courses.index', ['ma_chuyen_nganh' => $this->major2->ma_chuyen_nganh]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Courses/Index')
            ->where('courses.total', 1)
            ->where('courses.data.0.ma_hoc_phan', 'COS101')
        );
    }

    // AC-07: Môn học không có chuyên ngành vẫn xuất hiện khi không áp dụng bộ lọc chuyên ngành
    public function test_ac07_course_without_major_appears_when_no_major_filter(): void
    {
        $response = $this->get(route('courses.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Courses/Index')
            ->where('courses.total', 3)
        );

        // Kiểm tra môn GEN101 (không có chuyên ngành) có mặt trong danh sách
        $coursesData = $response->viewData('page')['props']['courses']['data'];
        $found = collect($coursesData)->firstWhere('ma_hoc_phan', 'GEN101');
        $this->assertNotNull($found);
        $this->assertEmpty($found['chuyen_nganhs']);
    }

    // AC-08: Pagination hoạt động và giữ query string
    public function test_ac08_pagination_and_query_string_preservation(): void
    {
        // Tạo thêm 15 môn học để vượt quá perPage = 12
        MonHoc::factory()->count(15)->create([
            'ma_khoa' => $this->faculty1->ma_khoa,
        ]);

        $response = $this->get(route('courses.index', [
            'ma_khoa' => $this->faculty1->ma_khoa,
            'page'    => 2,
        ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Courses/Index')
            ->where('courses.current_page', 2)
            ->has('courses.links')
        );
    }

    // AC-09: Khi không có môn nào khớp, trả về kết quả rỗng (Empty State)
    public function test_ac09_empty_state_when_no_course_matches(): void
    {
        $response = $this->get(route('courses.index', ['search' => 'KHONG_TON_TAI_XYZ']));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Courses/Index')
            ->where('courses.total', 0)
            ->has('courses.data', 0)
        );
    }

    // AC-10: Refresh hoặc truy cập trực tiếp URL có query parameters vẫn nạp đúng props filters
    public function test_ac10_filters_props_match_query_parameters(): void
    {
        $response = $this->get(route('courses.index', [
            'search'          => 'COS',
            'ma_khoa'         => (string) $this->faculty1->ma_khoa,
            'ma_chuyen_nganh' => (string) $this->major1->ma_chuyen_nganh,
        ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Courses/Index')
            ->where('filters.search', 'COS')
            ->where('filters.ma_khoa', (string) $this->faculty1->ma_khoa)
            ->where('filters.ma_chuyen_nganh', (string) $this->major1->ma_chuyen_nganh)
        );
    }

    // AC-12: Sinh viên (SINH_VIEN) và Cựu sinh viên (CUU_SINH_VIEN) đều truy cập được bình thường
    public function test_ac12_student_and_alumni_and_guest_can_access(): void
    {
        // 1. Guest truy cập
        $this->get(route('courses.index'))->assertOk();

        // 2. Student truy cập
        $student = NguoiDung::factory()->create(['vai_tro' => 'SINH_VIEN']);
        $this->actingAs($student)
            ->get(route('courses.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Courses/Index'));

        // 3. Alumni truy cập
        $alumni = NguoiDung::factory()->create(['vai_tro' => 'CUU_SINH_VIEN']);
        $this->actingAs($alumni)
            ->get(route('courses.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Courses/Index'));
    }
}
