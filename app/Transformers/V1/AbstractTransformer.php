<?php

namespace App\Transformers\V1;

use App\Models\ApiKey;
use App\Models\User;
use League\Fractal\TransformerAbstract;

abstract class AbstractTransformer extends TransformerAbstract
{
    public function __construct(
        protected ?User $user = null,
        protected ?ApiKey $apiKey = null,
    ) {}

    protected function modifyForUser(array $data, object $object): array
    {
        if (! $this->isAdminContext()) {
            return $data;
        }

        return array_merge(
            [
                'id' => $object->id,
            ],
            $data,
            $this->getAdminProperties($object),
            [
                'created_at' => $object->created_at->toIso8601String(),
                'updated_at' => $object->updated_at->toIso8601String(),
            ]
        );
    }

    /**
     * Returns true when the caller should receive the admin-equivalent payload.
     * Any ApiKey is treated as admin-equivalent because the apikey guard already
     * rejects disabled keys before they reach a transformer.
     */
    protected function isAdminContext(): bool
    {
        if ($this->apiKey !== null) {
            return true;
        }

        return $this->user !== null && $this->user->hasRole('admin');
    }

    protected function getAdminProperties(object $object): array
    {
        return [];
    }
}
