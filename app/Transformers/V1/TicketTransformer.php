<?php

namespace App\Transformers\V1;

use App\Models\Ticket;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\Primitive;
use League\Fractal\TransformerAbstract;

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
            return new Primitive(null);
        }

        return $this->item($ticket->user, new AbridgedUserTransformer);
    }

    public function includeSeat(Ticket $ticket): Item|Primitive
    {
        if ($ticket->seat === null) {
            return new Primitive(null);
        }

        return $this->item($ticket->seat, new AbridgedSeatTransformer);
    }
}
