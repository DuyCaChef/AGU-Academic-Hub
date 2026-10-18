<?php

use Illuminate\Support\Facades\Route;

Route::prefix('auth')->name('auth.')->group(function (): void {
    // Authentication and KYC routes belong to the Identity module.
});
