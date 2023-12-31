<?php

use App\Http\Controllers\Api\V1\SeatingPlanController;
use Illuminate\Support\Facades\Route;

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

Route::prefix('v1')->name('api.v1.')->middleware('auth:sanctum')->group(function () {
    Route::resource('events.seatingplans', SeatingPlanController::class)
        ->only(['index', 'show'])
        ->middleware('can:see,event')
        ->scoped();
});
