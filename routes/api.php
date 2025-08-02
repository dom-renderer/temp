<?php

use Illuminate\Support\Facades\Route;

Route::post('login', [\App\Http\Controllers\API\JobController::class,'login']);

Route::middleware('auth:api')->group(function () {
    Route::get('jobs', [\App\Http\Controllers\API\JobController::class, 'jobs']);
    Route::post('punch-in', [\App\Http\Controllers\API\JobController::class, 'punchIn']);
    Route::post('punch-out', [\App\Http\Controllers\API\JobController::class, 'punchOut']);
});