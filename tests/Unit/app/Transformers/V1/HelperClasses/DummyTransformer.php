<?php

namespace Tests\Unit\app\Transformers\V1\HelperClasses;

use App\Transformers\V1\AbstractTransformer;

class DummyTransformer extends AbstractTransformer
{
    protected function getAdminPropertiesPublic(object $object): array
    {
        return $this->getAdminProperties($object);
    }

    // Provide admin properties for testing
    protected function getAdminProperties(object $object): array
    {
        return ['admin' => true];
    }
}
