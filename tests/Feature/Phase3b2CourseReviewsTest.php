<?php

namespace Tests\Feature;

use App\Enums\TrangThaiDanhGiaMonHoc;
use App\Enums\VaiTroNguoiDung;
use App\Models\BinhChonDanhGia;
use App\Models\DanhGiaMonHoc;
use App\Models\Khoa;
use App\Models\MonHoc;
use App\Models\NguoiDung;
use App\Models\ViDiem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class Phase3b2CourseReviewsTest extends TestCase
{
    use RefreshDatabase;

    protected Khoa $faculty;
    protected MonHoc $course;
    protected NguoiDung $authorStudent;
    protected NguoiDung $voterStudent;
    protected NguoiDung $alumniUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->faculty = Khoa::create([
            'ten_khoa'     => 'Khoa Công nghệ Thông tin',
            'ky_hieu_khoa' => 'CNTT',
        ]);

        $this->course = MonHoc::create([
            'ma_khoa'     => $this->faculty->ma_khoa,
            'ma_hoc_phan' => 'COS101',
            'ten_mon_hoc' => 'Lập trình Căn bản',
            'so_tin_chi'  => 3,
            'mo_ta'       => 'Học phần cơ sở lập trình đại cương.',
        ]);

        $this->authorStudent = NguoiDung::factory()->create([
            'ho_ten'      => 'Nguyễn Văn Tác Giả',
            'ma_khoa'     => $this->faculty->ma_khoa,
            'vai_tro'     => VaiTroNguoiDung::SINH_VIEN,
            'diem_uy_tin' => 100,
        ]);

        $this->voterStudent = NguoiDung::factory()->create([
            'ho_ten'      => 'Trần Thị Người Vote',
            'ma_khoa'     => $this->faculty->ma_khoa,
            'vai_tro'     => VaiTroNguoiDung::SINH_VIEN,
            'diem_uy_tin' => 80,
        ]);

        $this->alumniUser = NguoiDung::factory()->create([
            'ho_ten'      => 'Lê Cựu Sinh Viên',
            'ma_khoa'     => $this->faculty->ma_khoa,
            'vai_tro'     => VaiTroNguoiDung::CUU_SINH_VIEN,
            'diem_uy_tin' => 150,
        ]);
    }

    // 1. Tích hợp Tab Đánh giá trên Course Show (AC-01, AC-02)
    public function test_course_show_page_includes_reviews_and_review_stats(): void
    {
        // Tạo sẵn 2 đánh giá đã duyệt
        DanhGiaMonHoc::create([
            'ma_mon_hoc'      => $this->course->ma_mon_hoc,
            'ma_nguoi_dung'   => $this->authorStudent->ma_nguoi_dung,
            'hoc_ky'          => '2025-1',
            'diem_do_kho'     => 4,
            'diem_thuc_tien'  => 5,
            'diem_khoi_luong' => 3,
            'noi_dung'        => 'Môn học cực kỳ bổ ích và thực tế.',
            'an_danh'         => true,
            'trang_thai'      => TrangThaiDanhGiaMonHoc::DA_DUYET,
        ]);

        $response = $this->actingAs($this->voterStudent)
            ->get("/mon-hoc/{$this->course->ma_hoc_phan}");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Courses/Show')
            ->has('course')
            ->has('documents')
            ->has('reviews')
            ->has('thong_ke')
            ->where('thong_ke.tong_so_danh_gia', 1)
            ->where('thong_ke.avg_do_kho', 4)
            ->where('thong_ke.avg_thuc_tien', 5)
            ->where('thong_ke.avg_khoi_luong', 3)
        );
    }

    // 2. Tạo bài đánh giá mới hợp lệ (AC-04, AC-05)
    public function test_can_store_course_review_via_endpoint(): void
    {
        $response = $this->actingAs($this->authorStudent)
            ->post("/mon-hoc/{$this->course->ma_hoc_phan}/danh-gia", [
                'hoc_ky'          => '2025-2',
                'diem_do_kho'     => 3,
                'diem_thuc_tien'  => 4,
                'diem_khoi_luong' => 4,
                'nhan_xet'        => 'Giảng viên hướng dẫn nhiệt tình, bài tập thực hành sát thực tế.',
                'la_an_danh'      => true,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('danh_gia_mon_hoc', [
            'ma_mon_hoc'    => $this->course->ma_mon_hoc,
            'ma_nguoi_dung' => $this->authorStudent->ma_nguoi_dung,
            'hoc_ky'        => '2025-2',
            'diem_do_kho'   => 3,
            'an_danh'       => 1,
        ]);
    }

    // 3. Validation thang điểm từ 1 đến 5 sao
    public function test_validation_rejects_out_of_range_scores(): void
    {
        $response = $this->actingAs($this->authorStudent)
            ->post("/mon-hoc/{$this->course->ma_hoc_phan}/danh-gia", [
                'hoc_ky'          => '2025-2',
                'diem_do_kho'     => 6, // Vượt quá 5 sao
                'diem_thuc_tien'  => 0, // Nhỏ hơn 1 sao
                'diem_khoi_luong' => 3,
                'nhan_xet'        => 'Đánh giá thử nghiệm',
                'la_an_danh'      => true,
            ]);

        $response->assertSessionHasErrors(['diem_do_kho', 'diem_thuc_tien']);
    }

    // 4. Profanity Filter chặn nội dung dung tục (AC-06)
    public function test_profanity_filter_blocks_vulgar_review(): void
    {
        $response = $this->actingAs($this->authorStudent)
            ->post("/mon-hoc/{$this->course->ma_hoc_phan}/danh-gia", [
                'hoc_ky'          => '2025-2',
                'diem_do_kho'     => 4,
                'diem_thuc_tien'  => 4,
                'diem_khoi_luong' => 4,
                'nhan_xet'        => 'Môn học này đm vcl thật sự tệ hại.',
                'la_an_danh'      => true,
            ]);

        $response->assertSessionHasErrors(['nhan_xet']);
        $this->assertDatabaseMissing('danh_gia_mon_hoc', [
            'ma_mon_hoc' => $this->course->ma_mon_hoc,
            'hoc_ky'     => '2025-2',
        ]);
    }

    // 5. Ẩn danh 2 lớp: Không làm lộ ma_nguoi_dung / email / MSSV (AC-19, AC-20, AC-21)
    public function test_public_response_preserves_strict_anonymity(): void
    {
        $review = DanhGiaMonHoc::create([
            'ma_mon_hoc'      => $this->course->ma_mon_hoc,
            'ma_nguoi_dung'   => $this->authorStudent->ma_nguoi_dung,
            'hoc_ky'          => '2025-1',
            'diem_do_kho'     => 3,
            'diem_thuc_tien'  => 4,
            'diem_khoi_luong' => 3,
            'noi_dung'        => 'Đánh giá ẩn danh mẫu',
            'an_danh'         => true,
            'trang_thai'      => TrangThaiDanhGiaMonHoc::DA_DUYET,
        ]);

        $response = $this->actingAs($this->voterStudent)
            ->getJson("/mon-hoc/{$this->course->ma_hoc_phan}/danh-gia");

        $response->assertStatus(200);
        $reviewItem = $response->json('reviews.0');

        // Không chứa ma_nguoi_dung hoặc ID định danh
        $this->assertArrayNotHasKey('ma_nguoi_dung', $reviewItem);
        $this->assertArrayNotHasKey('ma_nguoi_danh_gia', $reviewItem);
        $this->assertArrayNotHasKey('email', $reviewItem);
        $this->assertArrayNotHasKey('mssv', $reviewItem);

        // Hiển thị tên Sinh viên ẩn danh
        $this->assertEquals('Sinh viên ẩn danh', $reviewItem['tac_gia']['ten_hien_thi']);
        $this->assertNull($reviewItem['tac_gia']['anh_dai_dien']);
        $this->assertFalse($reviewItem['co_quyen_chinh_sua']);
    }

    // 6. Community Voting: Upvote (+1) (AC-07, AC-17)
    public function test_community_upvote_records_positive_score(): void
    {
        $review = DanhGiaMonHoc::create([
            'ma_mon_hoc'      => $this->course->ma_mon_hoc,
            'ma_nguoi_dung'   => $this->authorStudent->ma_nguoi_dung,
            'hoc_ky'          => '2025-1',
            'diem_do_kho'     => 3,
            'diem_thuc_tien'  => 4,
            'diem_khoi_luong' => 3,
            'noi_dung'        => 'Đánh giá mẫu',
            'an_danh'         => true,
            'trang_thai'      => TrangThaiDanhGiaMonHoc::DA_DUYET,
        ]);

        $response = $this->actingAs($this->voterStudent)
            ->postJson("/danh-gia/{$review->ma_danh_gia}/vote", [
                'vote_type' => 1,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status'          => 'success',
            'action'          => 'voted',
            'my_vote'         => 1,
            'review_score'    => 1,
            'upvotes_count'   => 1,
            'downvotes_count' => 0,
        ]);

        $this->assertDatabaseHas('binh_chon_danh_gia', [
            'ma_danh_gia'    => $review->ma_danh_gia,
            'ma_nguoi_dung'  => $this->voterStudent->ma_nguoi_dung,
            'loai_binh_chon' => 1,
        ]);
    }

    // 7. Community Voting: Downvote (-1) (AC-08, AC-17)
    public function test_community_downvote_records_negative_score(): void
    {
        $review = DanhGiaMonHoc::create([
            'ma_mon_hoc'      => $this->course->ma_mon_hoc,
            'ma_nguoi_dung'   => $this->authorStudent->ma_nguoi_dung,
            'hoc_ky'          => '2025-1',
            'diem_do_kho'     => 3,
            'diem_thuc_tien'  => 4,
            'diem_khoi_luong' => 3,
            'noi_dung'        => 'Đánh giá mẫu',
            'an_danh'         => true,
            'trang_thai'      => TrangThaiDanhGiaMonHoc::DA_DUYET,
        ]);

        $response = $this->actingAs($this->voterStudent)
            ->postJson("/danh-gia/{$review->ma_danh_gia}/vote", [
                'vote_type' => -1,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status'          => 'success',
            'action'          => 'voted',
            'my_vote'         => -1,
            'review_score'    => -1,
            'upvotes_count'   => 0,
            'downvotes_count' => 1,
        ]);

        $this->assertDatabaseHas('binh_chon_danh_gia', [
            'ma_danh_gia'    => $review->ma_danh_gia,
            'ma_nguoi_dung'  => $this->voterStudent->ma_nguoi_dung,
            'loai_binh_chon' => -1,
        ]);
    }

    // 8. Hủy Upvote khi nhấp lại cùng lựa chọn (AC-09)
    public function test_cancel_upvote_returns_score_to_zero(): void
    {
        $review = DanhGiaMonHoc::create([
            'ma_mon_hoc'      => $this->course->ma_mon_hoc,
            'ma_nguoi_dung'   => $this->authorStudent->ma_nguoi_dung,
            'hoc_ky'          => '2025-1',
            'diem_do_kho'     => 3,
            'diem_thuc_tien'  => 4,
            'diem_khoi_luong' => 3,
            'noi_dung'        => 'Đánh giá mẫu',
            'an_danh'         => true,
            'trang_thai'      => TrangThaiDanhGiaMonHoc::DA_DUYET,
        ]);

        // Lần 1: Upvote
        $this->actingAs($this->voterStudent)
            ->postJson("/danh-gia/{$review->ma_danh_gia}/vote", ['vote_type' => 1]);

        // Lần 2: Nhấp lại Upvote -> Hủy vote
        $response = $this->actingAs($this->voterStudent)
            ->postJson("/danh-gia/{$review->ma_danh_gia}/vote", ['vote_type' => 1]);

        $response->assertStatus(200);
        $response->assertJson([
            'status'          => 'success',
            'action'          => 'cancelled',
            'my_vote'         => null,
            'review_score'    => 0,
            'upvotes_count'   => 0,
            'downvotes_count' => 0,
        ]);

        $this->assertDatabaseMissing('binh_chon_danh_gia', [
            'ma_danh_gia'   => $review->ma_danh_gia,
            'ma_nguoi_dung' => $this->voterStudent->ma_nguoi_dung,
        ]);
    }

    // 9. Hủy Downvote khi nhấp lại cùng lựa chọn (AC-10)
    public function test_cancel_downvote_returns_score_to_zero(): void
    {
        $review = DanhGiaMonHoc::create([
            'ma_mon_hoc'      => $this->course->ma_mon_hoc,
            'ma_nguoi_dung'   => $this->authorStudent->ma_nguoi_dung,
            'hoc_ky'          => '2025-1',
            'diem_do_kho'     => 3,
            'diem_thuc_tien'  => 4,
            'diem_khoi_luong' => 3,
            'noi_dung'        => 'Đánh giá mẫu',
            'an_danh'         => true,
            'trang_thai'      => TrangThaiDanhGiaMonHoc::DA_DUYET,
        ]);

        // Lần 1: Downvote
        $this->actingAs($this->voterStudent)
            ->postJson("/danh-gia/{$review->ma_danh_gia}/vote", ['vote_type' => -1]);

        // Lần 2: Nhấp lại Downvote -> Hủy vote
        $response = $this->actingAs($this->voterStudent)
            ->postJson("/danh-gia/{$review->ma_danh_gia}/vote", ['vote_type' => -1]);

        $response->assertStatus(200);
        $response->assertJson([
            'status'          => 'success',
            'action'          => 'cancelled',
            'my_vote'         => null,
            'review_score'    => 0,
            'upvotes_count'   => 0,
            'downvotes_count' => 0,
        ]);

        $this->assertDatabaseMissing('binh_chon_danh_gia', [
            'ma_danh_gia'   => $review->ma_danh_gia,
            'ma_nguoi_dung' => $this->voterStudent->ma_nguoi_dung,
        ]);
    }

    // 10. Chuyển đổi hướng bình chọn Upvote sang Downvote (AC-11)
    public function test_switch_from_upvote_to_downvote_adjusts_score_by_two(): void
    {
        $review = DanhGiaMonHoc::create([
            'ma_mon_hoc'      => $this->course->ma_mon_hoc,
            'ma_nguoi_dung'   => $this->authorStudent->ma_nguoi_dung,
            'hoc_ky'          => '2025-1',
            'diem_do_kho'     => 3,
            'diem_thuc_tien'  => 4,
            'diem_khoi_luong' => 3,
            'noi_dung'        => 'Đánh giá mẫu',
            'an_danh'         => true,
            'trang_thai'      => TrangThaiDanhGiaMonHoc::DA_DUYET,
        ]);

        // Đang Upvote (+1)
        BinhChonDanhGia::create([
            'ma_danh_gia'    => $review->ma_danh_gia,
            'ma_nguoi_dung'  => $this->voterStudent->ma_nguoi_dung,
            'loai_binh_chon' => 1,
            'huu_ich'        => true,
        ]);

        // Chuyển sang Downvote (-1)
        $response = $this->actingAs($this->voterStudent)
            ->postJson("/danh-gia/{$review->ma_danh_gia}/vote", [
                'vote_type' => -1,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status'          => 'success',
            'action'          => 'switched',
            'my_vote'         => -1,
            'review_score'    => -1, // Giảm từ +1 xuống -1 (chênh lệch 2 điểm)
            'upvotes_count'   => 0,
            'downvotes_count' => 1,
        ]);

        $this->assertDatabaseHas('binh_chon_danh_gia', [
            'ma_danh_gia'    => $review->ma_danh_gia,
            'ma_nguoi_dung'  => $this->voterStudent->ma_nguoi_dung,
            'loai_binh_chon' => -1,
        ]);
    }

    // 11. Chuyển đổi hướng bình chọn Downvote sang Upvote (AC-12)
    public function test_switch_from_downvote_to_upvote_adjusts_score_by_two(): void
    {
        $review = DanhGiaMonHoc::create([
            'ma_mon_hoc'      => $this->course->ma_mon_hoc,
            'ma_nguoi_dung'   => $this->authorStudent->ma_nguoi_dung,
            'hoc_ky'          => '2025-1',
            'diem_do_kho'     => 3,
            'diem_thuc_tien'  => 4,
            'diem_khoi_luong' => 3,
            'noi_dung'        => 'Đánh giá mẫu',
            'an_danh'         => true,
            'trang_thai'      => TrangThaiDanhGiaMonHoc::DA_DUYET,
        ]);

        // Đang Downvote (-1)
        BinhChonDanhGia::create([
            'ma_danh_gia'    => $review->ma_danh_gia,
            'ma_nguoi_dung'  => $this->voterStudent->ma_nguoi_dung,
            'loai_binh_chon' => -1,
            'huu_ich'        => false,
        ]);

        // Chuyển sang Upvote (+1)
        $response = $this->actingAs($this->voterStudent)
            ->postJson("/danh-gia/{$review->ma_danh_gia}/vote", [
                'vote_type' => 1,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status'          => 'success',
            'action'          => 'switched',
            'my_vote'         => 1,
            'review_score'    => 1, // Tăng từ -1 lên +1 (chênh lệch 2 điểm)
            'upvotes_count'   => 1,
            'downvotes_count' => 0,
        ]);

        $this->assertDatabaseHas('binh_chon_danh_gia', [
            'ma_danh_gia'    => $review->ma_danh_gia,
            'ma_nguoi_dung'  => $this->voterStudent->ma_nguoi_dung,
            'loai_binh_chon' => 1,
        ]);
    }

    // 12. Tác giả không thể tự vote bài của chính mình (AC-13)
    public function test_author_cannot_vote_on_own_review(): void
    {
        $review = DanhGiaMonHoc::create([
            'ma_mon_hoc'      => $this->course->ma_mon_hoc,
            'ma_nguoi_dung'   => $this->authorStudent->ma_nguoi_dung,
            'hoc_ky'          => '2025-1',
            'diem_do_kho'     => 3,
            'diem_thuc_tien'  => 4,
            'diem_khoi_luong' => 3,
            'noi_dung'        => 'Bài viết của tác giả',
            'an_danh'         => true,
            'trang_thai'      => TrangThaiDanhGiaMonHoc::DA_DUYET,
        ]);

        $response = $this->actingAs($this->authorStudent)
            ->postJson("/danh-gia/{$review->ma_danh_gia}/vote", [
                'vote_type' => 1,
            ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('binh_chon_danh_gia', [
            'ma_danh_gia'   => $review->ma_danh_gia,
            'ma_nguoi_dung' => $this->authorStudent->ma_nguoi_dung,
        ]);
    }

    // 13. Người chưa đăng nhập không thể vote (AC-14)
    public function test_unauthenticated_user_cannot_vote(): void
    {
        $review = DanhGiaMonHoc::create([
            'ma_mon_hoc'      => $this->course->ma_mon_hoc,
            'ma_nguoi_dung'   => $this->authorStudent->ma_nguoi_dung,
            'hoc_ky'          => '2025-1',
            'diem_do_kho'     => 3,
            'diem_thuc_tien'  => 4,
            'diem_khoi_luong' => 3,
            'noi_dung'        => 'Bài viết mẫu',
            'an_danh'         => true,
            'trang_thai'      => TrangThaiDanhGiaMonHoc::DA_DUYET,
        ]);

        $response = $this->postJson("/danh-gia/{$review->ma_danh_gia}/vote", [
            'vote_type' => 1,
        ]);

        $response->assertStatus(401);
    }

    // 14. Point Isolation: Vote KHÔNG làm thay đổi User Reputation (AC-22)
    public function test_voting_does_not_modify_user_reputation(): void
    {
        $initialReputation = $this->authorStudent->diem_uy_tin;

        $review = DanhGiaMonHoc::create([
            'ma_mon_hoc'      => $this->course->ma_mon_hoc,
            'ma_nguoi_dung'   => $this->authorStudent->ma_nguoi_dung,
            'hoc_ky'          => '2025-1',
            'diem_do_kho'     => 3,
            'diem_thuc_tien'  => 4,
            'diem_khoi_luong' => 3,
            'noi_dung'        => 'Bài viết kiểm tra danh tiếng',
            'an_danh'         => true,
            'trang_thai'      => TrangThaiDanhGiaMonHoc::DA_DUYET,
        ]);

        // Người dùng khác Upvote
        $this->actingAs($this->voterStudent)
            ->postJson("/danh-gia/{$review->ma_danh_gia}/vote", ['vote_type' => 1]);

        $this->authorStudent->refresh();
        $this->assertEquals($initialReputation, $this->authorStudent->diem_uy_tin, 'Vote không được phép thay đổi điểm uy tín của tác giả.');

        // Người dùng khác Downvote
        $this->actingAs($this->alumniUser)
            ->postJson("/danh-gia/{$review->ma_danh_gia}/vote", ['vote_type' => -1]);

        $this->authorStudent->refresh();
        $this->assertEquals($initialReputation, $this->authorStudent->diem_uy_tin, 'Downvote không được phép trừ điểm uy tín của tác giả.');
    }

    // 15. Point Isolation: Vote KHÔNG làm thay đổi Wallet Points (AC-23)
    public function test_voting_does_not_modify_wallet_points(): void
    {
        $review = DanhGiaMonHoc::create([
            'ma_mon_hoc'      => $this->course->ma_mon_hoc,
            'ma_nguoi_dung'   => $this->authorStudent->ma_nguoi_dung,
            'hoc_ky'          => '2025-1',
            'diem_do_kho'     => 3,
            'diem_thuc_tien'  => 4,
            'diem_khoi_luong' => 3,
            'noi_dung'        => 'Bài viết kiểm tra ví điểm',
            'an_danh'         => true,
            'trang_thai'      => TrangThaiDanhGiaMonHoc::DA_DUYET,
        ]);

        $authorWallet = ViDiem::firstOrCreate(
            ['ma_nguoi_dung' => $this->authorStudent->ma_nguoi_dung],
            ['so_du_diem' => 50, 'diem_dong_bang' => 0]
        );

        $initialBalance = $authorWallet->so_du_diem;

        $this->actingAs($this->voterStudent)
            ->postJson("/danh-gia/{$review->ma_danh_gia}/vote", ['vote_type' => 1]);

        $authorWallet->refresh();
        $this->assertEquals($initialBalance, $authorWallet->so_du_diem, 'Vote không được làm thay đổi số dư ví của tác giả.');
        $this->assertDatabaseMissing('giao_dich_diem', [
            'ma_nguoi_dung' => $this->authorStudent->ma_nguoi_dung,
        ]);
    }

    // 16. Không thể vote bài đánh giá bị ẩn hoặc chưa duyệt (AC-24)
    public function test_cannot_vote_on_unapproved_or_pending_review(): void
    {
        $pendingReview = DanhGiaMonHoc::create([
            'ma_mon_hoc'      => $this->course->ma_mon_hoc,
            'ma_nguoi_dung'   => $this->authorStudent->ma_nguoi_dung,
            'hoc_ky'          => '2025-1',
            'diem_do_kho'     => 3,
            'diem_thuc_tien'  => 4,
            'diem_khoi_luong' => 3,
            'noi_dung'        => 'Bài viết chờ duyệt',
            'an_danh'         => true,
            'trang_thai'      => TrangThaiDanhGiaMonHoc::CHO_DUYET,
        ]);

        $response = $this->actingAs($this->voterStudent)
            ->postJson("/danh-gia/{$pendingReview->ma_danh_gia}/vote", ['vote_type' => 1]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('binh_chon_danh_gia', [
            'ma_danh_gia' => $pendingReview->ma_danh_gia,
        ]);
    }

    // 17. Đối soát Review Score từ tổng hợp Upvote và Downvote (AC-17, AC-30)
    public function test_review_score_matches_sum_of_votes_across_multiple_users(): void
    {
        $review = DanhGiaMonHoc::create([
            'ma_mon_hoc'      => $this->course->ma_mon_hoc,
            'ma_nguoi_dung'   => $this->authorStudent->ma_nguoi_dung,
            'hoc_ky'          => '2025-1',
            'diem_do_kho'     => 3,
            'diem_thuc_tien'  => 4,
            'diem_khoi_luong' => 3,
            'noi_dung'        => 'Bài viết đối soát điểm',
            'an_danh'         => true,
            'trang_thai'      => TrangThaiDanhGiaMonHoc::DA_DUYET,
        ]);

        // Voter 1: Upvote (+1)
        $this->actingAs($this->voterStudent)
            ->postJson("/danh-gia/{$review->ma_danh_gia}/vote", ['vote_type' => 1]);

        // Voter 2 (Alumni): Upvote (+1)
        $this->actingAs($this->alumniUser)
            ->postJson("/danh-gia/{$review->ma_danh_gia}/vote", ['vote_type' => 1]);

        // Voter 3: Downvote (-1)
        $voter3 = NguoiDung::factory()->create(['ma_khoa' => $this->faculty->ma_khoa]);
        $this->actingAs($voter3)
            ->postJson("/danh-gia/{$review->ma_danh_gia}/vote", ['vote_type' => -1]);

        // Tổng: 2 Upvotes, 1 Downvote -> Score = +1
        $response = $this->actingAs($this->voterStudent)
            ->getJson("/mon-hoc/{$this->course->ma_hoc_phan}/danh-gia");

        $response->assertStatus(200);
        $reviewItem = $response->json('reviews.0');

        $this->assertEquals(2, $reviewItem['upvotes_count']);
        $this->assertEquals(1, $reviewItem['downvotes_count']);
        $this->assertEquals(1, $reviewItem['review_score']);
        $this->assertEquals(1, $reviewItem['my_vote']); // Voter 1 đang xem -> my_vote = 1
        $this->assertTrue($reviewItem['is_upvoted']);
        $this->assertFalse($reviewItem['is_downvoted']);
    }

    // 18. Ràng buộc CSDL: Mỗi người dùng chỉ có duy nhất 1 vote trên cùng 1 review (AC-25)
    public function test_database_unique_constraint_enforces_single_vote_per_user(): void
    {
        $review = DanhGiaMonHoc::create([
            'ma_mon_hoc'      => $this->course->ma_mon_hoc,
            'ma_nguoi_dung'   => $this->authorStudent->ma_nguoi_dung,
            'hoc_ky'          => '2025-1',
            'diem_do_kho'     => 3,
            'diem_thuc_tien'  => 4,
            'diem_khoi_luong' => 3,
            'noi_dung'        => 'Bài viết ràng buộc duy nhất',
            'an_danh'         => true,
            'trang_thai'      => TrangThaiDanhGiaMonHoc::DA_DUYET,
        ]);

        BinhChonDanhGia::create([
            'ma_danh_gia'    => $review->ma_danh_gia,
            'ma_nguoi_dung'  => $this->voterStudent->ma_nguoi_dung,
            'loai_binh_chon' => 1,
            'huu_ich'        => true,
        ]);

        // Thử chèn bản ghi trùng lặp trực tiếp vào database phải ném lỗi QueryException
        $this->expectException(\Illuminate\Database\QueryException::class);

        BinhChonDanhGia::create([
            'ma_danh_gia'    => $review->ma_danh_gia,
            'ma_nguoi_dung'  => $this->voterStudent->ma_nguoi_dung,
            'loai_binh_chon' => -1,
            'huu_ich'        => false,
        ]);
    }

    // 19. Tương thích ngược với endpoint Helpful cũ (AC-27)
    public function test_legacy_helpful_endpoint_works_seamlessly(): void
    {
        $review = DanhGiaMonHoc::create([
            'ma_mon_hoc'      => $this->course->ma_mon_hoc,
            'ma_nguoi_dung'   => $this->authorStudent->ma_nguoi_dung,
            'hoc_ky'          => '2025-1',
            'diem_do_kho'     => 3,
            'diem_thuc_tien'  => 4,
            'diem_khoi_luong' => 3,
            'noi_dung'        => 'Bài viết tương thích ngược',
            'an_danh'         => true,
            'trang_thai'      => TrangThaiDanhGiaMonHoc::DA_DUYET,
        ]);

        // Gọi endpoint cũ
        $response = $this->actingAs($this->voterStudent)
            ->post("/danh-gia/{$review->ma_danh_gia}/huu-ich");

        $response->assertRedirect();
        $this->assertDatabaseHas('binh_chon_danh_gia', [
            'ma_danh_gia'    => $review->ma_danh_gia,
            'ma_nguoi_dung'  => $this->voterStudent->ma_nguoi_dung,
            'loai_binh_chon' => 1,
        ]);

        // Gọi lần 2 để hủy
        $response2 = $this->actingAs($this->voterStudent)
            ->post("/danh-gia/{$review->ma_danh_gia}/huu-ich");

        $response2->assertRedirect();
        $this->assertDatabaseMissing('binh_chon_danh_gia', [
            'ma_danh_gia'   => $review->ma_danh_gia,
            'ma_nguoi_dung' => $this->voterStudent->ma_nguoi_dung,
        ]);
    }

    // 20. Xóa mềm đánh giá (AC-24)
    public function test_author_can_soft_delete_review(): void
    {
        $review = DanhGiaMonHoc::create([
            'ma_mon_hoc'      => $this->course->ma_mon_hoc,
            'ma_nguoi_dung'   => $this->authorStudent->ma_nguoi_dung,
            'hoc_ky'          => '2025-1',
            'diem_do_kho'     => 3,
            'diem_thuc_tien'  => 4,
            'diem_khoi_luong' => 3,
            'noi_dung'        => 'Bài viết chuẩn bị xóa',
            'an_danh'         => true,
            'trang_thai'      => TrangThaiDanhGiaMonHoc::DA_DUYET,
        ]);

        $response = $this->actingAs($this->authorStudent)
            ->delete("/danh-gia/{$review->ma_danh_gia}");

        $response->assertRedirect();
        $this->assertSoftDeleted('danh_gia_mon_hoc', [
            'ma_danh_gia' => $review->ma_danh_gia,
        ]);
    }
}

