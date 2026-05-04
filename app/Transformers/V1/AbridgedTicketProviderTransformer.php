<?php

namespace App\Transformers\V1;

use App\Models\TicketProvider;
use League\Fractal\TransformerAbstract;

/**
 * Returns a fixed minimal TicketProvider payload for use as a nested resource.
 *
 * Intentionally extends TransformerAbstract directly (not the project's
 * AbstractTransformer) so the output is context-independent and cannot
 * expose additional fields via modifyForUser regardless of the calling
 * context.
 */
class AbridgedTicketProviderTransformer extends TransformerAbstract
{
    public function transform(TicketProvider $provider): array
    {
        return [
            'id' => $provider->id,
            'code' => $provider->code,
            'name' => $provider->name,
        ];
    }
}
