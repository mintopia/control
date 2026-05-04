<?php

namespace App\Transformers\V1;

use App\Models\TicketType;
use League\Fractal\TransformerAbstract;

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
