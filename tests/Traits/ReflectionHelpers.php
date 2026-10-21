<?php

namespace Tests\Traits;

use ReflectionClass;

trait ReflectionHelpers
{
    /**
     * Invoke a protected/private method on an object from tests.
     *
     * @param object $obj
     * @param string $method
     * @param array $args
     * @return mixed
     */
    protected function callProtected(object $obj, string $method, array $args = [])
    {
        $ref = new ReflectionClass($obj);
        $m = $ref->getMethod($method);
        $m->setAccessible(true);
        return $m->invokeArgs($obj, $args);
    }

    /**
     * Assert an object implements the given interface.
     *
     * @param object $obj
     * @param string $interface
     * @return void
     */
    protected function assertImplementsInterface(object $obj, string $interface): void
    {
        $rc = new ReflectionClass($obj);
        \PHPUnit\Framework\Assert::assertTrue($rc->implementsInterface($interface));
    }
}
