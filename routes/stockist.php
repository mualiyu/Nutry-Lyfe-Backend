<?php

use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;


// products management
Route::middleware('auth:sanctum')->prefix("products")->group(function () {
    // get all products (admin, vendor & affiliate)
    Route::get('get-system-products', [ProductController::class, 'index']);

    Route::get('get-all-my-products', [ProductController::class, 'stockist_all_products']);

    Route::get('get-my-single-product', [ProductController::class, 'stockist_show']);

    Route::post('update-quantity/{product}', [ProductController::class, 'stockist_update_quantity']);

});
