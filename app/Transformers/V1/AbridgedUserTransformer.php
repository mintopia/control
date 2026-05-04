<?php

namespace App\Transformers\V1;

use App\Models\User;
use League\Fractal\TransformerAbstract;

/**
 * Returns a fixed minimal User payload for use as a nested resource.
 *
 * Intentionally extends TransformerAbstract directly (not the project's
 * AbstractTransformer) so the output is context-independent and cannot
 * expose additional fields via modifyForUser regardless of the calling
 * context.
 */
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
