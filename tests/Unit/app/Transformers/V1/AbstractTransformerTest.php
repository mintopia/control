<?php

namespace Tests\Unit\app\Transformers\V1;

use App\Models\User;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;
use Tests\Unit\app\Transformers\V1\HelperClasses\DummyObject;
use Tests\Unit\app\Transformers\V1\HelperClasses\DummyTransformer;
use Tests\Unit\app\Transformers\V1\HelperClasses\DummyTransformer2;

class AbstractTransformerTest extends TestCase
{
    protected function invokeMethod($object, $method, array $parameters = [])
    {
        $reflection = new ReflectionClass($object);
        $method = $reflection->getMethod($method);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }

    public function testModifyForUserReturnsDataForNonAdmin()
    {
        $user = $this->createMock(User::class);
        $user->method('hasRole')->willReturn(false);
        $transformer = new DummyTransformer($user);
        $object = new DummyObject();
        $data = ['foo' => 'bar'];
        $result = $this->invokeMethod($transformer, 'modifyForUser', [$data, $object]);
        $this->assertEquals($data, $result);
    }

    public function testModifyForUserReturnsAdminData()
    {
        $user = $this->createMock(User::class);
        $user->method('hasRole')->with('admin')->willReturn(true);
        $transformer = new DummyTransformer($user);
        $object = new DummyObject();
        $data = ['foo' => 'bar'];
        $result = $this->invokeMethod($transformer, 'modifyForUser', [$data, $object]);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('admin', $result);
        $this->assertArrayHasKey('created_at', $result);
        $this->assertArrayHasKey('updated_at', $result);
        $this->assertEquals('2022-01-01T00:00:00+00:00', $result['created_at']);
        $this->assertEquals('2022-01-02T00:00:00+00:00', $result['updated_at']);
    }

    public function testGetAdminPropertiesDirectly()
    {
        $transformer = new DummyTransformer($this->createMock(User::class));
        $object = new DummyObject();
        $m = new ReflectionMethod(DummyTransformer::class, 'getAdminPropertiesPublic');
        $m->setAccessible(true);
        $result = $m->invoke($transformer, $object);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('admin', $result);
        $this->assertTrue($result['admin']);
    }

    // Manually added test to check getAdminPropertiesPublic
    public function testGetAdminPropertiesReturnsEmptyList()
    {
        $transformer = new DummyTransformer2($this->createMock(User::class));
        $object = new DummyObject();

        $result = $transformer->getAdminPropertiesPublic($object);
        $this->assertEquals($result, []);
    }
}
