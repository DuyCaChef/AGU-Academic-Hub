<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Documents\Presentation\Http\Controllers\DocumentController;

Route::prefix('documents')->name('documents.')->group(function (): void {
    Route::get('/', [DocumentController::class, 'index'])->name('index');
});
