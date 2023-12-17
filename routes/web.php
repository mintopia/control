<?php

use App\Http\Controllers\Admin\ClanMembershipController as AdminClanMembershipController;
use App\Http\Controllers\Admin\EmailAddressController as AdminEmailAddressController;
use App\Http\Controllers\Admin\EventMappingController;
use App\Http\Controllers\Admin\LinkedAccountController as AdminLinkedAccountController;
use App\Http\Controllers\Admin\ClanController as AdminClanController;
use App\Http\Controllers\Admin\EventController as AdminEventController;
use App\Http\Controllers\Admin\SeatController as AdminSeatController;
use App\Http\Controllers\Admin\SeatingPlanController as AdminSeatingPlanController;
use App\Http\Controllers\Admin\SocialProviderController;
use App\Http\Controllers\Admin\TicketController as AdminTicketController;
use App\Http\Controllers\Admin\TicketProviderController;
use App\Http\Controllers\Admin\TicketTypeController;
use App\Http\Controllers\Admin\TicketTypeMappingController;
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
Route::middleware('auth:sanctum')->group(function() {

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
        Route::get('seating/{event}', [SeatingPlanController::class, 'show'])->name('seatingplans.show');

        Route::middleware('can:pick,seat')->group(function() {
            Route::get('seats/{seat}', [SeatController::class, 'edit'])->name('seats.edit');
            Route::match(['PUT', 'PATCH'], 'seats/{seat}', [SeatController::class, 'update'])->name('seats.update');
        });

        Route::get('/admin/unimpersonate', [AdminHomeController::class, 'unimpersonate'])->name('admin.unimpersonate');

        Route::middleware('can:admin')->name('admin.')->prefix('admin')->group(function() {
            Route::get('/', [AdminHomeController::class, 'dashboard'])->name('dashboard');

            Route::resource('events', AdminEventController::class);
            Route::get('events/{event}/delete', [AdminEventController::class, 'delete'])->name('events.delete');
            Route::get('events/{event}/export', [AdminEventController::class, 'export_tickets'])->name('events.export');

            Route::resource('events.mappings', EventMappingController::class)->except(['index', 'show'])->scoped();
            Route::get('events/{event}/mappings/{mapping}/delete', [EventMappingController::class, 'delete'])->name('events.mappings.delete')->scopeBindings();

            Route::resource('events.seatingplans', AdminSeatingPlanController::class)->except(['index'])->scoped();
            Route::get('events/{event}/seatingplans/{seatingplan}/refresh', [AdminSeatingPlanController::class, 'refresh'])->name('events.seatingplans.refresh')->scopeBindings();
            Route::get('events/{event}/seatingplans/{seatingplan}/up', [AdminSeatingPlanController::class, 'up'])->name('events.seatingplans.up')->scopeBindings();
            Route::get('events/{event}/seatingplans/{seatingplan}/down', [AdminSeatingPlanController::class, 'down'])->name('events.seatingplans.down')->scopeBindings();
            Route::get('events/{event}/seatingplans/{seatingplan}/delete', [AdminSeatingPlanController::class, 'delete'])->name('events.seatingplans.delete')->scopeBindings();
            Route::get('events/{event}/seatingplans/{seatingplan}/export', [AdminSeatingPlanController::class, 'export'])->name('events.seatingplans.export')->scopeBindings();
            Route::get('events/{event}/seatingplans/{seatingplan}/import', [AdminSeatingPlanController::class, 'import'])->name('events.seatingplans.import')->scopeBindings();
            Route::post('events/{event}/seatingplans/{seatingplan}/import', [AdminSeatingPlanController::class, 'import_process'])->name('events.seatingplans.import_process')->scopeBindings();
            Route::resource('events.seatingplans.seats', AdminSeatController::class)->except(['index'])->scoped();
            Route::get('events/{event}/seatingplans/{seatingplan}/seats/{seat}/delete', [AdminSeatController::class, 'delete'])->name('events.seatingplans.seats.delete')->scopeBindings();
            Route::get('events/{event}/seatingplans/{seatingplan}/seats/{seat}/unseat', [AdminSeatController::class, 'unseat'])->name('events.seatingplans.seats.unseat')->scopeBindings();

            Route::resource('events.tickettypes', TicketTypeController::class)->except(['index'])->scoped();
            Route::get('events/{event}/tickettypes/{tickettype}/delete', [TicketTypeController::class, 'delete'])->name('events.tickettypes.delete')->scopeBindings();
            Route::resource('events.tickettypes.mappings', TicketTypeMappingController::class)->except(['show', 'index'])->scoped();
            Route::get('/events/{event}/tickettypes/{tickettype}/mappings/{mapping}/delete', [TicketTypeMappingController::class, 'delete'])->name('events.tickettypes.mappings.delete')->scopeBindings();

            Route::resource('tickets', AdminTicketController::class);
            Route::get('tickets/{ticket}/delete', [AdminTicketController::class, 'delete'])->name('tickets.delete');

            Route::resource('clans', AdminClanController::class);
            Route::get('clans/{clan}/delete', [AdminClanController::class, 'delete'])->name('clans.delete');
            Route::get('clans/{clan}/regenerate', [AdminClanController::class, 'regenerate'])->name('clans.regenerate');

            Route::resource('clans.members', AdminClanMembershipController::class)->only(['edit', 'update', 'destroy'])->scoped();
            Route::get('clans/{clan}/members/{member}/delete', [AdminClanMembershipController::class, 'delete'])->name('clans.members.delete')->scopeBindings();

            Route::resource('users', AdminUserController::class);
            Route::get('users/{user}/delete', [AdminUserController::class, 'delete'])->name('users.delete');
            Route::get('users/{user}/impersonate', [AdminUserController::class, 'impersonate'])->name('users.impersonate');
            Route::get('users/{user}/sync', [AdminUserController::class, 'sync_tickets'])->name('users.sync');

            Route::resource('users.emails', AdminEmailAddressController::class)->except(['index'])->scoped();
            Route::get('users/{user}/emails/{email}', [AdminEmailAddressController::class, 'delete'])->name('users.emails.delete')->scopeBindings();

            Route::get('users/{user}/accounts/{account}', [AdminLinkedAccountController::class, 'delete'])->name('users.accounts.delete')->scopeBindings();
            Route::delete('users/{user}/accounts/{account}', [AdminLinkedAccountController::class, 'destroy'])->name('users.accounts.destroy')->scopeBindings();

            Route::get('settings', [AdminSettingController::class, 'index'])->name('settings.index');
            Route::get('settings/ticketproviders/{provider}/edit', [TicketProviderController::class, 'edit'])->name('settings.ticketproviders.edit');
            Route::match(['PUT', 'PATCH'], 'settings/ticketproviders/{provider}', [TicketProviderController::class, 'update'])->name('settings.ticketproviders.update');
            Route::get('settings/ticketproviders/{provider}/clearcache', [TicketProviderController::class, 'clearcache'])->name('settings.ticketproviders.clearcache');
            Route::get('settings/socialproviders/{provider}/edit', [SocialProviderController::class, 'edit'])->name('settings.socialproviders.edit');
            Route::match(['PUT', 'PATCH'], 'settings/socialproviders/{provider}', [SocialProviderController::class, 'update'])->name('settings.socialproviders.update');
        });
    });
});

// Guest-only routes
Route::middleware('guest')->group(function() {
    Route::get('login', [UserController::class, 'login'])->name('login');
    Route::get('login/{socialprovider:code}', [UserController::class, 'login_redirect'])->name('login.redirect');
    Route::get('login/{socialprovider:code}/return', [UserController::class, 'login_return'])->name('login.return');
});
