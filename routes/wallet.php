<?php

use App\Http\Controllers\FundWalletController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/fund', [FundWalletController::class, 'initiateFunding']);
    Route::post('/verify-funding', [FundWalletController::class, 'verifyFunding']);

    // convert pv to naira
    Route::post('/convert-pv', [FundWalletController::class, 'convertPvToNaira']);

    //transfer wallet funds
    Route::post('/transfer-funds', [FundWalletController::class, 'transferFunds']);


    Route::post('/transaction-history', [FundWalletController::class, 'transactionHistory']);
});
