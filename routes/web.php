<?php

use App\Http\Controllers\Admin\HomeController as AdminHomeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\EmailAddressController;
use Illuminate\Support\Facades\Route;


// Always available
Route::get('logout', [UserController::class, 'logout'])->name('logout');

// Guest-only routes
Route::middleware('guest')->group(function() {
    Route::get('login', [UserController::class, 'login'])->name('login');
    Route::get('login/{socialprovider:code}', [UserController::class, 'login_redirect'])->name('login.redirect');
    Route::get('login/{socialprovider:code}/return', [UserController::class, 'login_return'])->name('login.return');
});

// Authenticated routes
Route::middleware('auth')->group(function() {
    Route::get('/', [HomeController::class, 'home'])->name('home');

    Route::get('/profile', [UserController::class, 'profile'])->name('user.profile');

    Route::get('/profile/emails/new', [EmailAddressController::class, 'create'])->name('emails.create');
    Route::post('/profile/emails', [EmailAddressController::class, 'store'])->name('emails.store');
    Route::middleware('can:update,emailaddress')->group(function() {
        Route::get('/profile/emails/{emailaddress}/verify', [EmailAddressController::class, 'verify'])->name('emails.verify');
        Route::get('/profile/emails/{emailaddress}/verify/{code}', [EmailAddressController::class, 'verify_code'])->name('emails.verify.code');
        Route::post('/profile/emails/{emailaddress}/verify', [EmailAddressController::class, 'verify_process'])->name('emails.verify.process');
        Route::get('/profile/emails/{emailaddress}/verify/resend', [EmailAddressController::class, 'verify_resend'])->name('emails.verify.resend');
        Route::get('/profile/emails/{emailaddress}/delete', [EmailAddressController::class, 'delete'])->name('emails.delete');
        Route::delete('/profile/emails/{emailaddress}', [EmailAddressController::class, 'destroy'])->name('emails.destroy');
    });

    Route::get('/profile/accounts/link/{socialprovider:code}', [HomeController::class, 'home'])->name('accounts.redirect');
    Route::get('/profile/accounts/link/{socialprovider:code}/return', [HomeController::class, 'home'])->name('accounts.return');

    Route::middleware('can:admin')->name('admin.')->prefix('admin')->group(function() {
        Route::get('/', [AdminHomeController::class, 'dashboard'])->name('dashboard');
    });
});
