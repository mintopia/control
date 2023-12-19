<?php

namespace App\Transformers\V1;

use App\Models\User;
use League\Fractal\TransformerAbstract;

abstract class AbstractTransformer extends TransformerAbstract
{
    public function __construct(protected ?User $user = null)
    {

    }

    protected function modifyForUser(array $data, object $object): array
    {
        if (!$this->user || !$this->user->hasRole('admin')) {
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

    protected function getAdminProperties(object $object): array
    {
        return [];
    }
}
