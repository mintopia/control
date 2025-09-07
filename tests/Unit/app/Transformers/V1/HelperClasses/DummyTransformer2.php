<?php

namespace Tests\Unit\app\Transformers\V1\HelperClasses;

use App\Transformers\V1\AbstractTransformer;

class DummyTransformer2 extends AbstractTransformer
{
    public function getAdminPropertiesPublic(object $object): array
    {
        return $this->getAdminProperties($object);
    }
}
