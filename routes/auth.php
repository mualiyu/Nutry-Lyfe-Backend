<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;


// Main API for Version One
# Register
Route::post('register/{is}', [AuthController::class, 'register']); //is = affliate or vendor
# Register
Route::middleware('auth:sanctum')->post('admin/register', [AuthController::class, 'register_admin']);
# Verify email
Route::post('email/verify', [AuthController::class, 'verifyEmail']);
# login
Route::post('login', [AuthController::class, 'login']);
# Logout
Route::middleware('auth:sanctum')->post('logout', [AuthController::class, 'logout']);
# forgot password
Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
# recover
Route::post('verify-code', [AuthController::class, 'verifyPin']);
# reset
Route::post('reset-password', [AuthController::class, 'resetPassword']);
