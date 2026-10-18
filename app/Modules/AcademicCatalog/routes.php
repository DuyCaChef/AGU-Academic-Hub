<?php

use Illuminate\Support\Facades\Route;
use App\Modules\AcademicCatalog\Presentation\Http\Controllers\CourseController;

Route::prefix('courses')->name('courses.')->group(function (): void {
    Route::get('/', [CourseController::class, 'index'])->name('index');
});
