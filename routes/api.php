<?php

use App\Http\Controllers\Api\BackendTokenController;
use App\Http\Controllers\Api\FinancialVoucherController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('v1/backend-auth')
    ->name('api.backend-auth.')
    ->controller(BackendTokenController::class)
    ->group(function (): void {
        Route::middleware('throttle:backend-token')->group(function (): void {
            Route::post('token', 'issue')->name('token');
            Route::post('refresh', 'refresh')->name('refresh');
        });

        Route::post('revoke', 'revoke')
            ->middleware('auth:sanctum')
            ->name('revoke');
    });

Route::prefix('v1/financial')
    ->middleware(['auth:sanctum', 'throttle:backend-api'])
    ->name('api.financial.')
    ->controller(FinancialVoucherController::class)
    ->group(function (): void {
        Route::post('vouchers', 'registerVoucher')
            ->middleware('ability:vouchers:create')
            ->name('vouchers.store');
        Route::post('dls', 'registerDL')
            ->middleware('ability:dls:create')
            ->name('dls.store');
        Route::post('parties', 'generateParty')
            ->middleware('ability:parties:create')
            ->name('parties.store');
    });
