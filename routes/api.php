<?php

use App\Http\Controllers\AccountPackageController;
use App\Http\Controllers\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::get('/error', function () {
    return response()->json([
        'status' => false,
        'message' => "Not Authenticated"
    ], 422);
})->name('not_auth');

// Account packages
Route::get('account-packages/all', [AccountPackageController::class, 'get_all']);
Route::middleware('auth:sanctum')->post('account-packages/create', [AccountPackageController::class, 'store']);
Route::middleware('auth:sanctum')->post('account-packages/delete/{id}', [AccountPackageController::class, 'destroy']);


// include Auth.php
Route::prefix('auth')->group(base_path('routes/auth.php'));

// include Profile.php
Route::prefix('profile')->group(base_path('routes/profile.php'));

// include Admin.php
Route::prefix('admin')->group(base_path('routes/admin.php'));

// include Networker.php
Route::prefix('networker')->group(base_path('routes/networker.php'));

// include Stockist.php
Route::prefix('stockist')->group(base_path('routes/stockist.php'));
