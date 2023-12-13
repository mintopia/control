<?php

use App\Http\Controllers\Admin\EventController as AdminEventController;
use App\Http\Controllers\Admin\TicketController as AdminTicketController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\HomeController as AdminHomeController;
use App\Http\Controllers\ClanController;
use App\Http\Controllers\ClanMembershipController;
use App\Http\Controllers\LinkedAccountController;
use App\Http\Controllers\SeatController;
use App\Http\Controllers\SeatingPlanController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\EmailAddressController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;


// Always available
Route::get('logout', [UserController::class, 'logout'])->name('logout');
Route::any('webhooks/tickets/{ticketprovider:code}', [WebhookController::class, 'tickets'])->name('webhooks.tickets');


// Authenticated routes
Route::middleware('auth')->group(function() {

    Route::get('login/signup', [UserController::class, 'signup'])->name('login.signup');
    Route::match(['PUT', 'PATCH'], 'login/signup', [UserController::class, 'signup_process'])->name('login.signup.process');

    Route::middleware(\App\Http\Middleware\RedirectOnFirstLoginMiddleware::class)->group(function() {

        Route::get('/', [HomeController::class, 'home'])->name('home');

        Route::get('/profile', [UserController::class, 'profile'])->name('user.profile');
        Route::get('/profile/edit', [UserController::class, 'edit'])->name('user.profile.edit');
        Route::match(['PATCH', 'PUT'], '/profile', [UserController::class, 'update'])->name('user.profile.update');

        Route::get('/profile/emails/new', [EmailAddressController::class, 'create'])->name('emails.create');
        Route::post('/profile/emails', [EmailAddressController::class, 'store'])->name('emails.store');
        Route::middleware('can:update,emailaddress')->group(function() {
            Route::get('/profile/emails/{emailaddress}/verify', [EmailAddressController::class, 'verify'])->name('emails.verify');
            Route::get('/profile/emails/{emailaddress}/verify/resend', [EmailAddressController::class, 'verify_resend'])->name('emails.verify.resend');
            Route::get('/profile/emails/{emailaddress}/verify/code', [EmailAddressController::class, 'verify_code'])->name('emails.verify.code');
            Route::post('/profile/emails/{emailaddress}/verify', [EmailAddressController::class, 'verify_process'])->name('emails.verify.process');
            Route::get('/profile/emails/{emailaddress}/delete', [EmailAddressController::class, 'delete'])->name('emails.delete');
            Route::delete('/profile/emails/{emailaddress}', [EmailAddressController::class, 'destroy'])->name('emails.destroy');
        });

        Route::get('/profile/accounts/{socialprovider:code}/link', [LinkedAccountController::class, 'create'])->name('linkedaccounts.create');
        Route::get('/profile/accounts/{socialprovider:code}/return', [LinkedAccountController::class, 'store'])->name('linkedaccounts.store');

        Route::middleware('can:update,linkedaccount')->group(function() {
            Route::get('/profile/accounts/{linkedaccount}/delete', [LinkedAccountController::class, 'store'])->name('linkedaccounts.delete');
            Route::delete('/profile/accounts/{linkedaccount}', [LinkedAccountController::class, 'store'])->name('linkedaccounts.destroy');
        });

        Route::resource('clans', ClanController::class)->only(['index', 'create', 'store']);
        Route::middleware('can:view,clan')->group(function() {
            Route::resource('clans', ClanController::class)->only(['show']);
        });
        Route::middleware('can:update,clan')->group(function() {
            Route::resource('clans', ClanController::class)->only(['edit', 'update', 'destroy']);
            Route::get('clans/{clan}/delete', [ClanController::class, 'delete'])->name('clans.delete');
            Route::post('clans/{clan}/regenerate', [ClanController::class, 'regenerate'])->name('clans.regenerate');
        });

        Route::post('clans/join', [ClanMembershipController::class, 'store'])->name('clans.members.store');
        Route::prefix('clans/{clan}/members')->name('clans.members.')->group(function() {
            Route::middleware('can:update,clanmembership')->group(function() {
                Route::get('{clanmembership}/edit', [ClanMembershipController::class, 'edit'])->name('edit');
                Route::match(['PUT', 'PATCH'], '{clanmembership}', [ClanMembershipController::class, 'update'])->name('update');
            });
            Route::middleware('can:delete,clanmembership')->group(function() {
                Route::get('{clanmembership}/delete', [ClanMembershipController::class, 'delete'])->name('delete');
                Route::delete('{clanmembership}', [ClanMembershipController::class, 'destroy'])->name('destroy');
            });
        });

        Route::get('tickets', [TicketController::class, 'index'])->name('tickets.index');
        Route::post('tickets/transfer', [TicketController::class, 'transfer'])->name('tickets.transfer');
        Route::middleware('can:update,ticket')->group(function() {
            Route::resource('tickets', TicketController::class)->only(['show', 'update']);
        });

        Route::get('seating', [SeatingPlanController::class, 'index'])->name('seatingplans.index');
        Route::get('seating/{event:code}', [SeatingPlanController::class, 'show'])->name('seatingplans.show');

        Route::middleware('can:pick,seat')->group(function() {
            Route::get('seats/{seat}', [SeatController::class, 'edit'])->name('seats.edit');
            Route::match(['PUT', 'PATCH'], 'seats/{seat}', [SeatController::class, 'update'])->name('seats.update');
        });

        Route::get('/unimpersonate', [AdminHomeController::class, 'unimpersonate'])->name('admin.unimpersonate');

        Route::middleware('can:admin')->name('admin.')->prefix('admin')->group(function() {
            Route::get('/', [AdminHomeController::class, 'dashboard'])->name('dashboard');
            Route::resource('events', AdminEventController::class);
            Route::get('events/{event}/delete', [AdminEventController::class, 'delete'])->name('events.delete');
            Route::resource('tickets', AdminTicketController::class);
            Route::get('tickets/{ticket}/delete', [AdminTicketController::class, 'delete'])->name('tickets.delete');
            Route::resource('users', AdminUserController::class);
            Route::get('users/{user}/delete', [AdminUserController::class, 'delete'])->name('users.delete');
            Route::get('users/{user}/impersonate', [AdminUserController::class, 'impersonate'])->name('users.impersonate');
            Route::get('settings', [AdminSettingController::class, 'edit'])->name('settings.edit');
            Route::match(['PUT', 'PATCH'], 'settings', [AdminSettingController::class, 'update'])->name('settings.update');
        });
    });
});

// Guest-only routes
Route::middleware('guest')->group(function() {
    Route::get('login', [UserController::class, 'login'])->name('login');
    Route::get('login/{socialprovider:code}', [UserController::class, 'login_redirect'])->name('login.redirect');
    Route::get('login/{socialprovider:code}/return', [UserController::class, 'login_return'])->name('login.return');
});
