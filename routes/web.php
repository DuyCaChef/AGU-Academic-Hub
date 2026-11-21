<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Admin\AdminAuditLogController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminRawStudentController;
use App\Http\Controllers\Admin\AdminStorageController;
use App\Http\Controllers\Admin\AdminSystemSettingController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\FirstTimePasswordController;
use App\Http\Controllers\Auth\KycController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\ProfileController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\AcademicCatalog\AcademicCatalogController;
use App\Http\Controllers\AcademicCatalog\CourseController;
use App\Http\Controllers\AcademicCatalog\FacultyController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->name('home');
Route::get('/trang-chu', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');
Route::get('/test-khach-layout', fn () => inertia()->render('TestGuest'));
Route::get('/test-admin-layout', fn () => inertia()->render('TestAdmin'));


// Tuyến đường Bảng điều khiển Quản trị (Dành cho Moderator và Admin)
Route::middleware(['auth', 'role:KIEM_DUYET_VIEN,QUAN_TRI_VIEN'])
    ->prefix('quan-tri')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/', AdminDashboardController::class)->name('dashboard');
    });

// Tuyến đường Quản trị Hệ thống (Dành riêng cho Quản trị viên - QUAN_TRI_VIEN)
Route::middleware(['auth', 'role:QUAN_TRI_VIEN'])
    ->prefix('quan-tri')
    ->name('admin.')
    ->group(function (): void {

        // Quản lý người dùng và phân quyền (RBAC)
        Route::prefix('nguoi-dung')->name('users.')->group(function (): void {
            Route::get('/', [AdminUserController::class, 'index'])->name('index');
            Route::get('/{ma_nguoi_dung}', [AdminUserController::class, 'show'])->name('show');
            Route::put('/{ma_nguoi_dung}/trang-thai', [AdminUserController::class, 'toggleStatus'])->name('status');
            Route::put('/{ma_nguoi_dung}/phan-quyen', [AdminUserController::class, 'updatePermissions'])->name('permissions');
            Route::post('/{ma_nguoi_dung}/vi-pham', [AdminUserController::class, 'recordViolation'])->name('violations');
        });

        // Chuyển hướng tương thích URL cũ /quan-tri/trang-thai về danh sách người dùng
        Route::get('/trang-thai', function () {
            return redirect()->route('admin.users.index');
        });

        // Theo dõi và quản lý dung lượng lưu trữ
        Route::prefix('luu-tru')->name('storage.')->group(function (): void {
            Route::get('/', [AdminStorageController::class, 'index'])->name('index');
            Route::post('/don-dep-thung-rac', [AdminStorageController::class, 'purgeExpiredTrash'])->name('purge-expired-trash');
        });

        // Nhật ký kiểm toán nghiệp vụ Admin (Audit Logs)
        Route::prefix('nhat-ky-kiem-toan')->name('audit-logs.')->group(function (): void {
            Route::get('/', [AdminAuditLogController::class, 'index'])->name('index');
            Route::get('/{id}', [AdminAuditLogController::class, 'show'])->name('show');
        });

        // Quản lý cấu hình hệ thống
        Route::prefix('cau-hinh')->name('settings.')->group(function (): void {
            Route::get('/', [AdminSystemSettingController::class, 'index'])->name('index');
            Route::put('/{khoa}', [AdminSystemSettingController::class, 'update'])->name('update');
        });

        // Quản lý danh sách sinh viên thô (Raw Student Roster)
        Route::prefix('sinh-vien-raw')->name('raw-students.')->group(function (): void {
            Route::get('/', [AdminRawStudentController::class, 'index'])->name('index');
            Route::post('/nap', [AdminRawStudentController::class, 'import'])->name('import');
            Route::put('/{id}', [AdminRawStudentController::class, 'update'])->name('update');
            Route::delete('/{id}', [AdminRawStudentController::class, 'destroy'])->name('destroy');
        });
    });


// =========================================================================
// 1. DANH MỤC KHOA & MÔN HỌC (PUBLIC / TRA CỨU CÔNG KHAI)
// =========================================================================

// Danh mục Khoa / Bộ môn
Route::prefix('khoa')->name('faculties.')->group(function () {
    Route::get('/', [FacultyController::class, 'index'])->name('index');
    Route::get('/{id}/mon-hoc', [FacultyController::class, 'layDanhSachMonHoc'])->name('courses');
    Route::get('/chuyen-nganh/{id}/mon-hoc', [FacultyController::class, 'layDanhSachMonHocTheoChuyenNganh'])->name('majors.courses');
});

// Danh mục Môn học
Route::prefix('mon-hoc')->name('courses.')->group(function () {
    Route::get('/', [CourseController::class, 'index'])->name('index');
    Route::get('/{course_code}', [CourseController::class, 'show'])->name('show');
    Route::get('/{course_code}/tai-lieu', [CourseController::class, 'documents'])->name('documents');
});

// =========================================================================
// 2. KHU VỰC QUẢN TRỊ DANH MỤC HỌC THUẬT (ADMIN CATALOG MANAGEMENT)
// =========================================================================
Route::middleware(['auth', 'role:QUAN_TRI_VIEN'])
    ->prefix('quan-tri/danh-muc')
    ->name('admin.catalog.')
    ->group(function () {
        Route::get('/', [AcademicCatalogController::class, 'index'])->name('index');

        // Quản trị Khoa / Bộ môn
        Route::post('/khoa', [AcademicCatalogController::class, 'storeFaculty'])->name('faculties.store');
        Route::put('/khoa/{maKhoa}', [AcademicCatalogController::class, 'updateFaculty'])->name('faculties.update');
        Route::put('/khoa/{maKhoa}/trang-thai', [AcademicCatalogController::class, 'toggleFaculty'])->name('faculties.toggle');

        // Quản trị Chuyên ngành
        Route::post('/chuyen-nganh', [AcademicCatalogController::class, 'storeMajor'])->name('majors.store');
        Route::put('/chuyen-nganh/{maChuyenNganh}', [AcademicCatalogController::class, 'updateMajor'])->name('majors.update');
        Route::put('/chuyen-nganh/{maChuyenNganh}/trang-thai', [AcademicCatalogController::class, 'toggleMajor'])->name('majors.toggle');

        // Quản trị Môn học
        Route::post('/mon-hoc', [AcademicCatalogController::class, 'storeCourse'])->name('courses.store');
        Route::put('/mon-hoc/{maMonHoc}', [AcademicCatalogController::class, 'updateCourse'])->name('courses.update');
        Route::put('/mon-hoc/{maMonHoc}/trang-thai', [AcademicCatalogController::class, 'toggleCourse'])->name('courses.toggle');
    });


// =========================================================================
// 1. TUYẾN ĐƯỜNG DÀNH CHO KHÁCH CHƯA ĐĂNG NHẬP (GUEST)
// =========================================================================
Route::middleware('guest')->group(function (): void {
    // Đăng ký hồ sơ KYC Cựu sinh viên
    Route::get('/cuu-sinh-vien/dang-ky', [KycController::class, 'hienThiFormDangKy'])
        ->name('alumni.register');
    Route::post('/cuu-sinh-vien/dang-ky', [KycController::class, 'luuDangKyCuuSinhVien'])
        ->name('alumni.register.store');

    // Đăng ký tài khoản sinh viên
    Route::get('/dang-ky', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/dang-ky', [RegisteredUserController::class, 'store']);
    Route::get('/register', [RegisteredUserController::class, 'create']);
    Route::post('/register', [RegisteredUserController::class, 'store']);

    // Đăng nhập hệ thống (hỗ trợ cả URI tiếng Việt và alias tiếng Anh)
    Route::get('/dang-nhap', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/dang-nhap', [AuthenticatedSessionController::class, 'store']);
    Route::get('/login', [AuthenticatedSessionController::class, 'create']);
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);

    // Quên và đặt lại mật khẩu
    Route::get('/quen-mat-khau', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');
    Route::post('/quen-mat-khau', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');
    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create']);
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store']);

    Route::get('/dat-lai-mat-khau/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');
    Route::post('/dat-lai-mat-khau', [NewPasswordController::class, 'store'])
        ->name('password.store');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create']);
    Route::post('/reset-password', [NewPasswordController::class, 'store']);
});

// =========================================================================
// 2. TUYẾN ĐƯỜNG DÀNH CHO NGƯỜI DÙNG ĐÃ ĐĂNG NHẬP (AUTH)
// =========================================================================
Route::middleware('auth')->group(function (): void {
    // Đổi mật khẩu lần đầu cho sinh viên mới kích hoạt
    Route::get('/mat-khau-lan-dau', [FirstTimePasswordController::class, 'hienThiFormDoiMatKhau'])
        ->name('password.change');
    Route::post('/mat-khau-lan-dau', [FirstTimePasswordController::class, 'capNhatMatKhau'])
        ->name('password.change.update');

    // Quản lý hồ sơ cá nhân
    Route::get('/ho-so', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/ho-so', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/ho-so', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/profile', [ProfileController::class, 'edit']);
    Route::patch('/profile', [ProfileController::class, 'update']);
    Route::delete('/profile', [ProfileController::class, 'destroy']);

    // Xác minh email
    Route::get('/xac-minh-email', [EmailVerificationController::class, 'prompt'])
        ->name('verification.notice');
    Route::get('/xac-minh-email/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/thong-bao-xac-minh', [EmailVerificationController::class, 'notification'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
    Route::get('/verify-email', [EmailVerificationController::class, 'prompt']);
    Route::get('/verify-email/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1']);
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'notification'])
        ->middleware('throttle:6,1');

    // Xác nhận mật khẩu và đổi mật khẩu
    Route::get('/xac-nhan-mat-khau', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');
    Route::post('/xac-nhan-mat-khau', [ConfirmablePasswordController::class, 'store']);
    Route::get('/confirm-password', [ConfirmablePasswordController::class, 'show']);
    Route::post('/confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('/mat-khau', [PasswordController::class, 'update'])->name('password.update');
    Route::put('/password', [PasswordController::class, 'update']);

    // Đăng xuất
    Route::post('/dang-xuat', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy']);
});

// =========================================================================
// 3. QUẢN TRỊ HÀNG ĐỢI DUYỆT KYC (ADMIN & MODERATOR)
// =========================================================================
Route::middleware(['auth', 'role:KIEM_DUYET_VIEN,QUAN_TRI_VIEN'])->group(function (): void {
    // Alias điều hướng nhanh
    Route::prefix('quan-tri/kyc')->name('kyc.')->group(function (): void {
        Route::get('/', function () {
            return redirect()->route('admin.kyc.index');
        })->name('queue');
    });

    // Hàng đợi duyệt và thao tác phê duyệt / từ chối hồ sơ cựu sinh viên
    Route::prefix('quan-tri')->name('admin.')->group(function (): void {
        Route::get('/duyet-kyc', [KycController::class, 'hangDoiKyc'])->name('kyc.index');
        Route::post('/duyet-kyc/duyet-hang-loat', [KycController::class, 'pheDuyetHangLoat'])->name('kyc.bulk-approve');
        Route::put('/duyet-kyc/{ma_xac_thuc}/duyet', [KycController::class, 'pheDuyetHoSo'])->name('kyc.approve');
        Route::put('/duyet-kyc/{ma_xac_thuc}/tu-choi', [KycController::class, 'tuChoiHoSo'])->name('kyc.reject');
    });
});

// Endpoint xem trước file minh chứng nhạy cảm (Private PII) qua Signed URL 30 phút
Route::middleware(['auth', 'signed'])
    ->get('/kyc/xem-anh/{ma_xac_thuc}', [KycController::class, 'xemTruocMinhChung'])
    ->name('kyc.preview');

require __DIR__ . '/documents.php';
require __DIR__ . '/community.php';
