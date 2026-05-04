<?php

namespace App\Transformers\V1;

use App\Models\Ticket;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\Primitive;
use League\Fractal\TransformerAbstract;

/**
 * Transforms a Ticket resource for the v1 API.
 *
 * Extends TransformerAbstract directly (not the project's AbstractTransformer)
 * because the top-level fields are fixed for any caller — there is no admin /
 * non-admin variation. The nested resources are abridged transformers, also
 * by design (see AbridgedUserTransformer etc.), so the response shape is
 * stable regardless of who is calling.
 */
class TicketTransformer extends TransformerAbstract
{
    /**
     * @var array<int, string>
     */
    protected array $defaultIncludes = [
        'event',
        'type',
        'provider',
        'user',
        'seat',
    ];

    /**
     * @var array<int, string>
     */
    protected array $availableIncludes = [
        'event',
        'type',
        'provider',
        'user',
        'seat',
    ];

    public function transform(Ticket $ticket): array
    {
        return [
            'id' => $ticket->id,
            'reference' => $ticket->reference,
            'external_id' => $ticket->external_id,
            'name' => $ticket->name,
            'created_at' => $ticket->created_at?->toIso8601String(),
        ];
    }

    public function includeEvent(Ticket $ticket): Item
    {
        // Nested event uses the public EventTransformer payload, not the
        // admin-shaped one. API key callers needing the full event view should
        // hit /api/v1/events/{code} directly.
        return $this->item($ticket->event, new EventTransformer);
    }

    public function includeType(Ticket $ticket): Item
    {
        return $this->item($ticket->type, new AbridgedTicketTypeTransformer);
    }

    public function includeProvider(Ticket $ticket): Item
    {
        return $this->item($ticket->provider, new AbridgedTicketProviderTransformer);
    }

    public function includeUser(Ticket $ticket): Item|Primitive
    {
        if ($ticket->user === null) {
            // Primitive(null) serialises to bare JSON `null`, which signals
            // "absent optional relation". $this->null() (NullResource) would
            // serialise to `{"data": []}` under DataArraySerializer, which
            // looks like an empty collection rather than an absent singular.
            return new Primitive(null);
        }

        return $this->item($ticket->user, new AbridgedUserTransformer);
    }

    public function includeSeat(Ticket $ticket): Item|Primitive
    {
        if ($ticket->seat === null) {
            // Primitive(null) serialises to bare JSON `null`, which signals
            // "absent optional relation". $this->null() (NullResource) would
            // serialise to `{"data": []}` under DataArraySerializer, which
            // looks like an empty collection rather than an absent singular.
            return new Primitive(null);
        }

        return $this->item($ticket->seat, new AbridgedSeatTransformer);
    }
}
