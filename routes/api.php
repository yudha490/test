<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\UserMissionController;
use App\Http\Controllers\Api\VoucherController;
use App\Http\Controllers\Api\VoucherExchangeController;
use App\Http\Controllers\Api\RewardController;

// Public routes (no authentication required)
Route::post('/register', [ApiController::class, 'register']);
Route::post('/login', [ApiController::class, 'login']);

Route::get('/ping', function () {
    return response()->json(['status' => 'ok']);
});

// Authenticated routes (require Sanctum authentication)
Route::middleware('auth:sanctum')->group(function () {
    // API Controller routes
    Route::post('/logout', [ApiController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // UserMissionController routes
    Route::get('/missions/active', [UserMissionController::class, 'activeMissions']);
    Route::get('/missions/{userMissionId}/progress', [UserMissionController::class, 'showMissionProgress']);
    Route::post('/missions/{userMissionId}/submit-proof', [UserMissionController::class, 'submitMissionProof']);

    // VoucherController routes
    Route::get('/vouchers', [VoucherController::class, 'index']);
    Route::get('/vouchers/{id}', [VoucherController::class, 'show']);

    // VoucherExchangeController routes
    Route::post('/vouchers/exchange', [VoucherExchangeController::class, 'exchange']);

    // RewardController routes
    Route::post('/rewards/exchange', [RewardController::class, 'exchange']);
    
});

