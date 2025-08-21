<?php

namespace Tests\Unit\app\Transformers\V1;

use Tests\TestCase;

use App\Models\User;
use App\Transformers\V1\AbstractTransformer;
use Illuminate\Support\Carbon;

class DummyTransformer extends AbstractTransformer
{
    protected function getAdminProperties(object $object): array
    {
        return ['admin' => true];
    }
}

class DummyObject
{
    public $id = 1;
    public $created_at;
    public $updated_at;
    public function __construct()
    {
        $this->created_at = Carbon::parse('2022-01-01T00:00:00Z');
        $this->updated_at = Carbon::parse('2022-01-02T00:00:00Z');
    }
}

class AbstractTransformerTest extends TestCase
{
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

    protected function invokeMethod($object, $method, array $parameters = [])
    {
        $reflection = new \ReflectionClass($object);
        $method = $reflection->getMethod($method);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }
}
