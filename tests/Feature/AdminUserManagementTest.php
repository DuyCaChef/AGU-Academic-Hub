<?php

namespace Tests\Feature;

use App\Enums\CapQuanTri;
use App\Enums\TrangThaiNguoiDung;
use App\Enums\VaiTroNguoiDung;
use App\Models\Khoa;
use App\Models\NguoiDung;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    // Khách vãng lai không được phép truy cập dashboard hoặc quản lý người dùng
    public function test_guest_cannot_access_admin_dashboard_or_users(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->get(route('admin.users.index'))->assertRedirect(route('login'));
    }

    // Sinh viên thường bị cấm truy cập khu vực quản trị
    public function test_regular_user_cannot_access_admin_dashboard_or_users(): void
    {
        $user = NguoiDung::factory()->create();

        $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
    }

    // Kiểm duyệt viên được vào Dashboard nhưng không được vào trang quản lý người dùng
    public function test_moderator_can_access_dashboard_but_not_user_management(): void
    {
        $moderator = NguoiDung::factory()->moderator()->create();

        $this->actingAs($moderator)->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($moderator)->get(route('admin.users.index'))->assertForbidden();
    }

    // Quản trị viên truy cập Dashboard với đầy đủ số liệu thống kê
    public function test_admin_can_view_dashboard_with_overview_stats(): void
    {
        $admin = NguoiDung::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertInertia(fn(Assert $page) => $page
            ->component('Admin/Dashboard/Index')
            ->has('stats')
            ->has('stats.pendingKyc')
            ->has('stats.pendingReports')
            ->has('stats.documentsToReview')
            ->has('stats.pendingRemovalRequests')
            ->has('stats.totalUsers')
            ->has('stats.totalDocuments')
            ->has('stats.storage')
        );
    }

    // Quản trị viên xem danh sách người dùng và áp dụng bộ lọc tìm kiếm
    public function test_admin_can_view_users_list_and_filter(): void
    {
        $admin = NguoiDung::factory()->admin()->create();
        $khoa = Khoa::create(['ten_khoa' => 'Sư phạm', 'ky_hieu_khoa' => 'SP']);

        $student = NguoiDung::factory()->create([
            'ho_ten' => 'Nguyễn Văn An',
            'email' => 'an@student.agu.edu.vn',
            'mssv' => 'DTH200001',
            'ma_khoa' => $khoa->ma_khoa,
            'vai_tro' => VaiTroNguoiDung::SINH_VIEN,
            'trang_thai' => TrangThaiNguoiDung::DANG_HOAT_DONG,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.users.index', [
            'search' => 'DTH200001',
            'vai_tro' => 'SINH_VIEN',
        ]));

        $response->assertOk();
        $response->assertInertia(fn(Assert $page) => $page
            ->component('Admin/Users/Index')
            ->has('users.data', 1)
            ->where('users.data.0.mssv', 'DTH200001')
            ->where('users.data.0.ho_ten', 'Nguyễn Văn An')
            ->has('faculties')
            ->has('filters')
        );
    }

    // Xem hồ sơ chi tiết người dùng kèm lịch sử trạng thái và phân quyền
    public function test_admin_can_view_user_detail_with_histories(): void
    {
        $admin = NguoiDung::factory()->admin()->create();
        $targetUser = NguoiDung::factory()->create(['ho_ten' => 'Lê Hoàng Nam']);

        $response = $this->actingAs($admin)->get(route('admin.users.show', $targetUser->ma_nguoi_dung));

        $response->assertOk();
        $response->assertInertia(fn(Assert $page) => $page
            ->component('Admin/Users/Show')
            ->has('user')
            ->where('user.ho_ten', 'Lê Hoàng Nam')
            ->has('permissions')
            ->has('faculties')
            ->has('statusHistory')
            ->has('roleHistory')
        );
    }

    // Không được phép tự thay đổi quyền hoặc tự khóa tài khoản của chính mình
    public function test_admin_cannot_manage_self(): void
    {
        $admin = NguoiDung::factory()->admin()->create();

        // Tự khóa chính mình -> lỗi
        $responseLock = $this->actingAs($admin)->put(route('admin.users.status', $admin->ma_nguoi_dung));
        $responseLock->assertSessionHas('error');

        // Tự đổi quyền chính mình -> lỗi
        $responseRole = $this->actingAs($admin)->put(route('admin.users.permissions', $admin->ma_nguoi_dung), [
            'vai_tro' => 'SINH_VIEN',
        ]);
        $responseRole->assertSessionHas('error');
    }

    // Đảo chiều trạng thái hoạt động tài khoản (Khóa và Mở khóa)
    public function test_admin_can_toggle_user_status(): void
    {
        $admin = NguoiDung::factory()->admin()->create();
        $target = NguoiDung::factory()->create([
            'trang_thai' => TrangThaiNguoiDung::DANG_HOAT_DONG,
        ]);

        // Khóa tài khoản
        $responseLock = $this->actingAs($admin)->put(route('admin.users.status', $target->ma_nguoi_dung), [
            'ly_do' => 'Vi phạm quy chế sử dụng',
        ]);
        $responseLock->assertSessionHas('warning');

        $this->assertDatabaseHas('nguoi_dung', [
            'ma_nguoi_dung' => $target->ma_nguoi_dung,
            'trang_thai' => TrangThaiNguoiDung::TAM_KHOA->value,
            'ly_do_khoa' => 'Vi phạm quy chế sử dụng',
        ]);

        $this->assertDatabaseHas('lich_su_trang_thai_nguoi_dung', [
            'ma_nguoi_dung' => $target->ma_nguoi_dung,
            'ma_nguoi_thuc_hien' => $admin->ma_nguoi_dung,
            'hanh_dong' => 'KHOA',
            'ly_do' => 'Vi phạm quy chế sử dụng',
        ]);

        // Mở khóa tài khoản
        $responseUnlock = $this->actingAs($admin)->put(route('admin.users.status', $target->ma_nguoi_dung));
        $responseUnlock->assertSessionHas('success');

        $this->assertDatabaseHas('nguoi_dung', [
            'ma_nguoi_dung' => $target->ma_nguoi_dung,
            'trang_thai' => TrangThaiNguoiDung::DANG_HOAT_DONG->value,
            'ly_do_khoa' => null,
        ]);
    }

    // Super Admin có thể phân quyền cho người dùng
    public function test_super_admin_can_update_user_permissions(): void
    {
        $superAdmin = NguoiDung::factory()->admin()->create([
            'cap_quan_tri' => CapQuanTri::TOI_CAO,
        ]);

        $student = NguoiDung::factory()->create([
            'vai_tro' => VaiTroNguoiDung::SINH_VIEN,
        ]);

        $response = $this->actingAs($superAdmin)->put(route('admin.users.permissions', $student->ma_nguoi_dung), [
            'vai_tro' => 'KIEM_DUYET_VIEN',
            'ly_do' => 'Bổ nhiệm kiểm duyệt viên mới',
        ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('nguoi_dung', [
            'ma_nguoi_dung' => $student->ma_nguoi_dung,
            'vai_tro' => VaiTroNguoiDung::KIEM_DUYET_VIEN->value,
            'ma_khoa' => null,
        ]);

        $this->assertDatabaseHas('lich_su_phan_quyen_nguoi_dung', [
            'ma_nguoi_dung' => $student->ma_nguoi_dung,
            'ma_nguoi_thuc_hien' => $superAdmin->ma_nguoi_dung,
            'vai_tro_cu' => VaiTroNguoiDung::SINH_VIEN->value,
            'vai_tro_moi' => VaiTroNguoiDung::KIEM_DUYET_VIEN->value,
            'ly_do' => 'Bổ nhiệm kiểm duyệt viên mới',
        ]);
    }

    // Không thể hạ cấp Super Admin cuối cùng của hệ thống
    public function test_cannot_demote_last_super_admin(): void
    {
        $superAdmin = NguoiDung::factory()->admin()->create([
            'cap_quan_tri' => CapQuanTri::TOI_CAO,
        ]);

        // Tạo 1 admin thường khác để thực hiện thao tác
        $admin = NguoiDung::factory()->admin()->create([
            'email' => 'admin_thuong@agu.edu.vn',
            'cap_quan_tri' => CapQuanTri::THUONG,
        ]);

        // Thử hạ cấp superAdmin khi chỉ có 1 superAdmin
        $response = $this->actingAs($admin)->put(route('admin.users.permissions', $superAdmin->ma_nguoi_dung), [
            'vai_tro' => 'QUAN_TRI_VIEN',
            'cap_quan_tri' => 'THUONG',
        ]);

        $response->assertSessionHas('error');
    }

    // Ghi nhận vi phạm người dùng và tự động khóa khi vi phạm từ lần thứ 2
    public function test_record_violation_and_auto_lock_on_repeat(): void
    {
        $admin = NguoiDung::factory()->admin()->create();
        $target = NguoiDung::factory()->create([
            'so_lan_vi_pham' => 0,
            'trang_thai' => TrangThaiNguoiDung::DANG_HOAT_DONG,
        ]);

        // Vi phạm lần 1 -> mức cảnh báo CANH_BAO
        $resp1 = $this->actingAs($admin)->post(route('admin.users.violations', $target->ma_nguoi_dung), [
            'ly_do' => 'Spam tài liệu',
        ]);
        $resp1->assertSessionHas('success');

        $this->assertDatabaseHas('nguoi_dung', [
            'ma_nguoi_dung' => $target->ma_nguoi_dung,
            'so_lan_vi_pham' => 1,
            'muc_canh_bao' => 'CANH_BAO',
            'trang_thai' => TrangThaiNguoiDung::DANG_HOAT_DONG->value,
        ]);

        // Vi phạm lần 2 -> tự động chuyển sang TAM_KHOA
        $resp2 = $this->actingAs($admin)->post(route('admin.users.violations', $target->ma_nguoi_dung), [
            'ly_do' => 'Tái diễn spam tài liệu',
        ]);
        $resp2->assertSessionHas('warning');

        $this->assertDatabaseHas('nguoi_dung', [
            'ma_nguoi_dung' => $target->ma_nguoi_dung,
            'so_lan_vi_pham' => 2,
            'muc_canh_bao' => 'TAM_KHOA',
            'trang_thai' => TrangThaiNguoiDung::TAM_KHOA->value,
            'ly_do_khoa' => 'Tái diễn spam tài liệu',
        ]);
    }
}

