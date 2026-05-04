<?php

namespace App\Transformers\V1;

use App\Models\TicketProvider;
use League\Fractal\TransformerAbstract;

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
