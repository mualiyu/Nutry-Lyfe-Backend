<?php

use App\Http\Controllers\NetworkerOrderController;
use App\Http\Controllers\NetworkerProductController;
use Illuminate\Support\Facades\Route;

// Stockist products Management
Route::middleware('auth:sanctum')->prefix("stockist")->group(function () {

    Route::get('get-all', [NetworkerProductController::class, 'get_all_stockist']);

    Route::get('get-all-products-by-stockist/{user}', [NetworkerProductController::class, 'get_all_products_by_stockist']);

    Route::get('get-single-stockist-product/{userProduct}', [NetworkerProductController::class, 'show_single_product_from_stockist']);
});

// System Product Management
Route::middleware('auth:sanctum')->prefix("system-products")->group(function () {

    Route::get('get-single-system-product/{product}', [NetworkerProductController::class, 'show_single_product']);
});

Route::middleware('auth:sanctum')->prefix("orders")->group(function () {

    Route::middleware('auth:sanctum')->get('all', [NetworkerOrderController::class, 'get_orders']);

    Route::middleware('auth:sanctum')->get('{order}', [NetworkerOrderController::class, 'get_order']);
});
