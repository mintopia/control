<?php

namespace Tests\Unit\App\Models\Traits;

use Tests\TestCase;

use App\Models\Traits\ToString;

class ToStringTest extends TestCase
{
    public function testToStringUsesToStringModelNameAndToStringNameIfAvailable()
    {
        $model = new class {
            use ToString;
            public $id = 42;
            public function toStringModelName() { return 'CustomModel'; }
            public function toStringName() { return 'TestName'; }
        };

        $this->assertEquals('[CustomModel:42] TestName', (string)$model);
    }

    public function testToStringFallsBackToClassNameAndNoName()
    {
        $model = new class {
            use ToString;
            public $id = 7;
        };

        // The anonymous class name will be something like "class@anonymous"
        $expectedClass = (new \ReflectionClass($model))->getShortName();
        $this->assertEquals("[$expectedClass:7]", (string)$model);
    }

    public function testToStringHandlesMissingId()
    {
        $model = new class {
            use ToString;
        };

        $expectedClass = (new \ReflectionClass($model))->getShortName();
        $this->assertEquals("[$expectedClass:#]", (string)$model);
    }

    public function testToStringHandlesOnlyToStringName()
    {
        $model = new class {
            use ToString;
            public $id = 5;
            public function toStringName() { return 'OnlyName'; }
        };

        $expectedClass = (new \ReflectionClass($model))->getShortName();
        $this->assertEquals("[$expectedClass:5] OnlyName", (string)$model);
    }

}
