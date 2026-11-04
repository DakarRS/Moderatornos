<?php

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\PersonController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:public-api')->group(function () {
    Route::get('categories', [CategoryController::class, 'index']);

    Route::apiResource('people', PersonController::class)->only(['index', 'show'])
        // Postgres raises an error on non-numeric or out-of-range ids instead of finding nothing.
        ->where(['person' => '[0-9]{1,18}']);
});
