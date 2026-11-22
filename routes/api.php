<?php

use App\Http\Controllers\Api\StudentAuthController;
use App\Http\Controllers\Api\StudentExamController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/student')->group(function () {
    Route::post('/login', [StudentAuthController::class, 'login'])->middleware('throttle:30,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [StudentAuthController::class, 'logout']);
        Route::get('/exams', [StudentExamController::class, 'index']);
        Route::get('/exams/{exam}/review', [StudentExamController::class, 'review']);
        Route::post('/exams/{exam}/start', [StudentExamController::class, 'start']);
        Route::post('/attempts/{attempt}/sync', [StudentExamController::class, 'sync']);
        Route::post('/attempts/{attempt}/violation', [StudentExamController::class, 'violation']);
        Route::post('/attempts/{attempt}/submit', [StudentExamController::class, 'submit']);
    });
});
