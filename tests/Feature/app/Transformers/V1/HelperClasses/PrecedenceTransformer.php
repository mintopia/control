<?php

namespace Tests\Feature\app\Transformers\V1\HelperClasses;

use App\Transformers\V1\AbstractTransformer;

class PrecedenceTransformer extends AbstractTransformer
{
    protected function getAdminProperties(object $object): array
    {
        return [
            // Intentionally override 'foo' from the original data
            'foo' => 'baz',
            'extra' => 'value_from_admin',
        ];
    }
}
