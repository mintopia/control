<?php

use App\Http\Controllers\Api\V1\EventController;
use App\Http\Controllers\Api\V1\SeatingPlanController;
use App\Http\Controllers\Api\V1\TicketController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        Route::resource('events.seatingplans', SeatingPlanController::class)
            ->only(['index', 'show'])
            ->middleware('can:see,event')
            ->scoped();
    });

    Route::middleware('auth:apikey')->group(function () {
        Route::get('events', [EventController::class, 'index'])->name('events.index');
        Route::get('events/{event:code}', [EventController::class, 'show'])->name('events.show');
        Route::get('tickets', [TicketController::class, 'index'])->name('tickets.index');
    });
});
