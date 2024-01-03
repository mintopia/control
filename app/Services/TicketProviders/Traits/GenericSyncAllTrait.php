<?php
namespace App\Services\TicketProviders\Traits;

use App\Models\EmailAddress;
use App\Services\TicketProviders\TicketTailorProvider;
use App\Services\TicketProviders\WooCommerceProvider;
use Carbon\Carbon;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\Log;

/**
 * @mixin TicketTailorProvider
 * @mixin WooCommerceProvider
 */
trait GenericSyncAllTrait
{
    public function syncAllTickets(?OutputStyle $output): void
    {
        // We need the type IDs for later
        $typeIds = $this->provider->types()->pluck('external_id')->toArray();

        $allTickets = collect($this->getTickets());

        // Break this down into manageable chunks
        $chunks = $allTickets->chunk(100);
        foreach ($chunks as $chunk) {
            // Prep some data for easy access
            $remoteTickets = [];
            $ids = [];
            foreach ($chunk as $ticket) {
                if (in_array($ticket->ticket_type_id, $typeIds)) {
                    $remoteTickets[$ticket->id] = $ticket;
                    $ids[$ticket->id] = $ticket->id;
                }
            }

            // Fetch all Control tickets for these TT tickets
            $tickets = $this->provider->tickets()->whereIn('external_id', $ids)->with('user')->get();
            foreach ($tickets as $ticket) {
                // We'll use $ids to remove any voided/non-existent tickets
                unset($ids[$ticket->external_id]);
                $remoteTicket = $remoteTickets[$ticket->external_id];

                if ($remoteTicket->status == 'voided') {
                    // Voided - we want to remove this ticket entirely.

                    $output->writeln(" {$ticket} has been voided, removing");
                    $ticket->delete();
                } else {
                    // For some reason we have the wrong original email - shouldn't happen, but it could
                    // TODO: Remove once we've sorted this on deployed instances
                    if ($ticket->original_email !== $remoteTicket->email) {
                        if ($output) {
                            $output->writeln(" Updating email address on {$ticket}");
                        }
                        $ticket->original_email = $remoteTicket->email;
                    }

                    // If we don't have a user on the ticket, try and find one
                    if (!$ticket->user) {
                        $email = EmailAddress::whereEmail($remoteTicket->email)
                            ->where('verified_at', '<=', Carbon::now())
                            ->with('user')->first();
                        if ($email) {
                            if ($output) {
                                $output->writeln(" Associating {$ticket} with {$email->user}");
                            }
                            $ticket->user()->associate($email->user);
                            Log::info("{$this->provider} {$ticket} has been allocated to {$email->user}");
                        }
                    }

                    if ($ticket->isDirty()) {
                        $ticket->save();
                    }
                }
            }

            // These are the tickets we didn't find internally
            foreach ($ids as $id) {
                // We don't care about voided ones
                if ($remoteTickets[$id]->status == 'voided') {
                    continue;
                }

                // Create our new ticket
                if ($output) {
                    $output->writeln(" Creating ticket for {$id} - {$remoteTickets[$id]->email}");
                }
                $ticket = $this->makeTicket(null, $remoteTickets[$id]);
                Log::info("{$this->provider} {$ticket} has been added for {$remoteTickets[$id]->email}");
                if ($output) {
                    $output->writeln(" Created {$ticket}");
                }
            }
        }
    }
}
