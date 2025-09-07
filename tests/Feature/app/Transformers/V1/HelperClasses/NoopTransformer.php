<?php

namespace Tests\Feature\app\Transformers\V1\HelperClasses;

use App\Transformers\V1\AbstractTransformer;

class NoopTransformer extends AbstractTransformer
{
    protected function getAdminProperties(object $object): array
    {
        return ['admin' => true];
    }
}
