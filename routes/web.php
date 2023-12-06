<?php

use App\Http\Controllers\Admin\HomeController as AdminHomeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;


// Always available
Route::get('logout', [AuthController::class, 'logout'])->name('logout');

// Guest-only routes
Route::middleware('guest')->group(function() {
    Route::get('login', [AuthController::class, 'login'])->name('login');
    Route::get('login/discord', [AuthController::class, 'login_redirect'])->name('login.discord.redirect');
    Route::get('login/discord/return', [AuthController::class, 'login_return'])->name('login.discord.return');
});

// Authenticated routes
Route::middleware('auth')->group(function() {
   Route::get('/', [HomeController::class, 'home'])->name('home');

   Route::middleware('can:admin')->name('admin.')->prefix('admin')->group(function() {
       Route::get('/', [AdminHomeController::class, 'dashboard'])->name('dashboard');
   });
});
