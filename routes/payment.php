<?php

use App\Http\Controllers\NetworkerOrderController;
use Illuminate\Support\Facades\Route;

// Place Order for Networker
Route::middleware('auth:sanctum')->prefix("orders")->group(function () {

    Route::middleware('auth:sanctum')->post('place-order', [NetworkerOrderController::class, 'place_order']);

    Route::middleware('auth:sanctum')->post('verify-payment', [NetworkerOrderController::class, 'verify_payment']);
});
