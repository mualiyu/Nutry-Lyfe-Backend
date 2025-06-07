<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// upload user images
Route::post('upload-user-image', [ProfileController::class, 'upload']);

Route::middleware('auth:sanctum')->group(function () {
    // get profile details
    Route::get('', [ProfileController::class, 'index']);
    // update profile details
    Route::post('update', [ProfileController::class, 'update']);
    // Delete Account
    Route::delete('delete-account', [ProfileController::class, 'destroy']);

    // Hierarchy routes
    Route::get('hierarchy-l1', [ProfileController::class, 'hierarchy_l1']);
    Route::get('hierarchy-all-downline', [ProfileController::class, 'hierarchy_all_downline']);
});
