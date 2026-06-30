<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Mobile App (Phase 23)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    Route::get('/ping', fn () => response()->json(['status' => 'ok', 'time' => now()]));
});