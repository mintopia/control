<?php

namespace App\Transformers\V1;

use App\Models\TicketType;
use League\Fractal\TransformerAbstract;

/**
 * Returns a fixed minimal TicketType payload for use as a nested resource.
 *
 * Intentionally extends TransformerAbstract directly (not the project's
 * AbstractTransformer) so the output is context-independent and cannot
 * expose additional fields via modifyForUser regardless of the calling
 * context.
 */
class AbridgedTicketTypeTransformer extends TransformerAbstract
{
    public function transform(TicketType $type): array
    {
        return [
            'id' => $type->id,
            'name' => $type->name,
        ];
    }
}
