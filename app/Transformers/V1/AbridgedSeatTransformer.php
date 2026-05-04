<?php

namespace App\Transformers\V1;

use App\Models\Seat;
use League\Fractal\TransformerAbstract;

/**
 * Returns a fixed minimal Seat payload for use as a nested resource.
 *
 * Intentionally extends TransformerAbstract directly (not the project's
 * AbstractTransformer) so the output is context-independent and cannot
 * expose additional fields via modifyForUser regardless of the calling
 * context.
 */
class AbridgedSeatTransformer extends TransformerAbstract
{
    public function transform(Seat $seat): array
    {
        return [
            'id' => $seat->id,
            'label' => $seat->label,
            'row' => $seat->row,
            'number' => $seat->number,
        ];
    }
}
