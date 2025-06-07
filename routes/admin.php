<?php

use App\Http\Controllers\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;



// products management
Route::middleware('auth:sanctum')->prefix("admin-products")->group(function () {
    // get all products (admin, vendor & affiliate)
    Route::get('', [ProductController::class, 'index']);

    Route::get('get-single', [ProductController::class, 'show']);

    Route::prefix('/product')->group(function(){
        // Create, Update & Delete profile details
        Route::post('create', [ProductController::class, 'create']);
        Route::post('update/{product}', [ProductController::class, 'update']);
        Route::delete('delete-product', [ProductController::class, 'destroy']);

        Route::post('update-status/admin', [ProductController::class, 'adminUpdateStatus']);
        Route::post('upload-file', [ProductController::class, 'upload']);
    });
});
