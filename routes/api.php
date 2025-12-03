<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Customer\TerminalController;
use App\Http\Controllers\Api\Customer\BannerController;
use App\Http\Controllers\Api\Customer\CustomerAuthController;
use App\Http\Controllers\Api\Customer\CustomerBookingController;
use App\Http\Controllers\Api\Customer\CustomerProfileController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::get('general-info', [TerminalController::class, 'general_info_admin']);
Route::prefix('customer')->group(function () {
    // Auth
    Route::post('auth/signup', [CustomerAuthController::class, 'signup']);
    Route::post('auth/login', [CustomerAuthController::class, 'login']);

    // Public trip search & seat selection endpoints
    Route::get('trips', [CustomerBookingController::class, 'trips']);
    Route::get('trips/details', [CustomerBookingController::class, 'tripDetails']);
    Route::get('trips/seat-map', [CustomerBookingController::class, 'tripDetails']);
    Route::get('banners', [BannerController::class, 'index']);
    Route::get('cities', [TerminalController::class, 'cities']);
    Route::get('terminals', [TerminalController::class, 'terminals']);
    Route::get('terminals/{city_id}', [TerminalController::class, 'terminalsByCity']);


    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [CustomerAuthController::class, 'logout']);

        // Profile
        Route::get('profile', [CustomerProfileController::class, 'show']);
        Route::put('profile', [CustomerProfileController::class, 'update']);
        Route::put('profile/password', [CustomerProfileController::class, 'changePassword']);

        // Bookings
        Route::post('bookings', [CustomerBookingController::class, 'store']);
        Route::get('bookings', [CustomerBookingController::class, 'history']);
        Route::post('bookings/{booking}/payment', [CustomerBookingController::class, 'pay']);
    });
});

// Public API routes for booking
Route::get('/routes/{route}/stops', function ($routeId) {
    $route = \App\Models\Route::with('routeStops.terminal.city')->findOrFail($routeId);

    return $route->routeStops->map(function ($stop) {
        return [
            'id' => $stop->id,
            'sequence' => $stop->sequence,
            'terminal' => [
                'id' => $stop->terminal->id,
                'name' => $stop->terminal->name,
                'city' => [
                    'id' => $stop->terminal->city->id,
                    'name' => $stop->terminal->city->name,
                ]
            ]
        ];
    });
});

// Legacy available routes endpoint (aliases trip search)
Route::get('/booking/available-routes', [CustomerBookingController::class, 'trips']);
