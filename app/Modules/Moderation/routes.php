<?php

use Illuminate\Support\Facades\Route;

Route::prefix('reports')->name('reports.')->group(function (): void {
    // Reports and moderation routes belong to the Moderation module.
});
