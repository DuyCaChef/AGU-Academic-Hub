<?php

use App\Modules\AcademicCatalog\Presentation\Http\Controllers\AcademicCatalogController;
use App\Modules\AcademicCatalog\Presentation\Http\Controllers\CourseController;
use App\Modules\AcademicCatalog\Presentation\Http\Controllers\FacultyController;
use Illuminate\Support\Facades\Route;

// =========================================================================
// 1. DANH MỤC KHOA & MÔN HỌC (PUBLIC / TRA CỨU CÔNG KHAI)
// =========================================================================

// Danh mục Khoa / Bộ môn
Route::prefix('khoa')->name('faculties.')->group(function () {
    Route::get('/', [FacultyController::class, 'index'])->name('index');
    Route::get('/{id}/mon-hoc', [FacultyController::class, 'layDanhSachMonHoc'])->name('courses');
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
