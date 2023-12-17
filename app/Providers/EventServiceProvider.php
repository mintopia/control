<?php

namespace App\Providers;

use App\Models\Clan;
use App\Models\EmailAddress;
use App\Models\Event;
use App\Models\Seat;
use App\Models\SeatingPlan;
use App\Models\TicketProvider;
use App\Models\User;
use App\Observers\ClanObserver;
use App\Observers\EmailAddressObserver;
use App\Observers\EventObserver;
use App\Observers\SeatingPlanObserver;
use App\Observers\SeatObserver;
use App\Observers\TicketProviderObserver;
use App\Observers\UserObserver;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $observers = [
        User::class => UserObserver::class,
        EmailAddress::class => EmailAddressObserver::class,
        Clan::class => ClanObserver::class,
        Event::class => EventObserver::class,
        Seat::class => SeatObserver::class,
        SeatingPlan::class => SeatingPlanObserver::class,
        TicketProvider::class => TicketProviderObserver::class,
    ];
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        \SocialiteProviders\Manager\SocialiteWasCalled::class => [
            \SocialiteProviders\Discord\DiscordExtendSocialite::class.'@handle',
            \SocialiteProviders\Steam\SteamExtendSocialite::class.'@handle',
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
