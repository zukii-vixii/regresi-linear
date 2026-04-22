<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DataRowController;
use App\Http\Controllers\DatasetController;
use App\Http\Controllers\RegressionController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => ['ok' => true, 'app' => config('app.name')]);

Route::post('/auth/register',         [AuthController::class, 'register']);
Route::post('/auth/login',            [AuthController::class, 'login']);
Route::post('/auth/forgot-password',  [AuthController::class, 'forgotPassword']);
Route::post('/auth/reset-password',   [AuthController::class, 'resetPassword']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get  ('/auth/me',      [AuthController::class, 'me']);
    Route::post ('/auth/logout',  [AuthController::class, 'logout']);
    Route::put  ('/auth/profile', [AuthController::class, 'updateProfile']);

    Route::get('/stats', [StatsController::class, 'index']);

    Route::get   ('/datasets',           [DatasetController::class, 'index']);
    Route::post  ('/datasets',           [DatasetController::class, 'store']);
    Route::get   ('/datasets/{dataset}', [DatasetController::class, 'show']);
    Route::put   ('/datasets/{dataset}', [DatasetController::class, 'update']);
    Route::delete('/datasets/{dataset}', [DatasetController::class, 'destroy']);

    Route::get   ('/data-rows',           [DataRowController::class, 'index']);
    Route::post  ('/data-rows',           [DataRowController::class, 'store']);
    Route::post  ('/data-rows/bulk',      [DataRowController::class, 'bulkStore']);
    Route::put   ('/data-rows/{dataRow}', [DataRowController::class, 'update']);
    Route::delete('/data-rows/{dataRow}', [DataRowController::class, 'destroy']);

    Route::post('/regression/compute', [RegressionController::class, 'compute']);
    Route::post('/regression/predict', [RegressionController::class, 'predict']);
    Route::get ('/regression/history', [RegressionController::class, 'history']);

    Route::middleware('admin')->group(function () {
        Route::get   ('/users',        [UserController::class, 'index']);
        Route::post  ('/users',        [UserController::class, 'store']);
        Route::get   ('/users/{user}', [UserController::class, 'show']);
        Route::put   ('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
    });
});
