<?php

namespace App\Transformers\V1;

use App\Models\Seat;
use League\Fractal\TransformerAbstract;

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
