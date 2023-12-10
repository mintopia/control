<?php
namespace App\Services\Contracts;

use App\Models\EmailAddress;
use App\Models\Ticket;
use App\Models\TicketProvider;
use Illuminate\Http\Request;

interface TicketProviderContract
{
    /**
     * Create a new Ticket Provider instance.
     *
     * @param TicketProvider|null $provider
     */
    public function __construct(?TicketProvider $provider = null);

    /**
     * Fetch configuration information for the provider.
     *
     * @return array
     */
    public function configMapping(): array;

    /**
     * Creates a TicketProvider object for this ticket provider.
     *
     * @return TicketProvider
     */
    public function install(): TicketProvider;

    /**
     * Process an incoming ticket webhook from the provider.
     *
     * @param Request $request
     * @return bool
     */
    public function processWebhook(Request $request): bool;

    /**
     * Fetch a ticket from the provider with its ticket ID. If the ticket doesn't exist, and we have a local user, it will
     * be created. Otherwise, we will update the local ticket based on the remote ticket.
     *
     * @param string $id
     * @return ?Ticket
     */
    public function syncTicket(string $id): ?Ticket;

    /**
     * Fetch tickets for the provided email and create them locally if they exist.
     * @param EmailAddress $email
     * @return void
     */
    public function syncTickets(EmailAddress $email): void;
}
