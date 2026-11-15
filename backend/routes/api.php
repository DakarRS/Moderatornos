<?php

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\PersonController;
use App\Http\Controllers\Api\SolutionController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:public-api')->group(function () {
    Route::get('categories', [CategoryController::class, 'index']);

    Route::apiResource('people', PersonController::class)->only(['index', 'show'])
        // Postgres raises an error on non-numeric or out-of-range ids instead of finding nothing.
        ->where(['person' => '[0-9]{1,18}']);

    Route::get('solutions', [SolutionController::class, 'index']);
    Route::post('solutions/match', [SolutionController::class, 'match']);
    Route::get('solutions/{slug}', [SolutionController::class, 'show'])->where('slug', '[a-z0-9-]{1,255}');
});
