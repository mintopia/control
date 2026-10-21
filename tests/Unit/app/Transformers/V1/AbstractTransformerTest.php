<?php

namespace Tests\Unit\app\Transformers\V1;

use App\Models\User;
use App\Transformers\V1\AbstractTransformer;
use Illuminate\Support\Carbon;
use Closure;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

class AbstractTransformerTest extends TestCase
{
    protected Closure $makeObject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->makeObject = function () {
            return (object) [
                'id' => 1,
                'created_at' => Carbon::parse('2022-01-01T00:00:00Z'),
                'updated_at' => Carbon::parse('2022-01-02T00:00:00Z'),
            ];
        };
    }

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
        $transformer = new class ($user) extends AbstractTransformer {
        };
        $object = ($this->makeObject)();
        $data = ['foo' => 'bar'];
        $result = $this->invokeMethod($transformer, 'modifyForUser', [$data, $object]);
        $this->assertEquals($data, $result);
    }

    public function testModifyForUserReturnsAdminData()
    {
        $user = $this->createMock(User::class);
        $user->method('hasRole')->with('admin')->willReturn(true);
        $transformer = new class ($user) extends AbstractTransformer {
            protected function getAdminProperties(object $object): array
            {
                return ['admin' => true];
            }
        };
        $object = ($this->makeObject)();
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
        $transformer = new class ($this->createMock(User::class)) extends AbstractTransformer {
            protected function getAdminProperties(object $object): array
            {
                return ['admin' => true];
            }
        };
        $object = ($this->makeObject)();
        $m = new ReflectionMethod(get_class($transformer), 'getAdminProperties');
        $m->setAccessible(true);
        $result = $m->invoke($transformer, $object);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('admin', $result);
        $this->assertTrue($result['admin']);
    }

    // Manually added test to check getAdminPropertiesPublic
    public function testGetAdminPropertiesReturnsEmptyList()
    {
        $transformer = new class ($this->createMock(User::class)) extends AbstractTransformer {
        };
        $object = ($this->makeObject)();

        $m = new ReflectionMethod(AbstractTransformer::class, 'getAdminProperties');
        $m->setAccessible(true);
        $result = $m->invoke($transformer, $object);
        $this->assertEquals($result, []);
    }
}
