<?php

namespace App\Transformers\V1;

use App\Models\User;
use League\Fractal\TransformerAbstract;

class AbridgedUserTransformer extends TransformerAbstract
{
    public function transform(User $user): array
    {
        return [
            'id' => $user->id,
            'nickname' => $user->nickname,
            'name' => $user->name,
            'email' => $user->primaryEmail?->email,
        ];
    }
}
