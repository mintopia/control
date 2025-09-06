<?php

namespace Tests\Unit\app\Transformers\V1;

use Tests\TestCase;

use App\Models\User;
use App\Transformers\V1\AbstractTransformer;
use Illuminate\Support\Carbon;

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

class NoopTransformer extends AbstractTransformer
{
    protected function getAdminProperties(object $object): array
    {
        return ['admin' => true];
    }
}

class AbstractTransformerExtraTest extends TestCase
{
    public function testModifyForUserAdminPropertyPrecedence()
    {
        $user = $this->createMock(User::class);
        $user->method('hasRole')->with('admin')->willReturn(true);

        $transformer = new PrecedenceTransformer($user);

        $object = new class {
            public $id = 2;
            public $created_at;
            public $updated_at;
            public function __construct()
            {
                $this->created_at = Carbon::parse('2022-01-03T00:00:00Z');
                $this->updated_at = Carbon::parse('2022-01-04T00:00:00Z');
            }
        };

        $data = ['foo' => 'bar'];
        $result = $this->invokeMethod($transformer, 'modifyForUser', [$data, $object]);

        // 'foo' from admin properties should take precedence over the original data
        $this->assertArrayHasKey('foo', $result);
        $this->assertEquals('baz', $result['foo']);

        // admin-provided extra key should be present
        $this->assertArrayHasKey('extra', $result);
        $this->assertEquals('value_from_admin', $result['extra']);

        // id and timestamps should be present and correct
        $this->assertEquals(2, $result['id']);
        $this->assertEquals('2022-01-03T00:00:00+00:00', $result['created_at']);
        $this->assertEquals('2022-01-04T00:00:00+00:00', $result['updated_at']);
    }

    public function testModifyForUserWithNullUserReturnsOriginalData()
    {
        // When no user is provided, modifyForUser should return the original data
        $transformer = new NoopTransformer(null);

        $object = new class {
            public $id = 5;
            public $created_at;
            public $updated_at;
            public function __construct()
            {
                $this->created_at = Carbon::parse('2022-02-01T00:00:00Z');
                $this->updated_at = Carbon::parse('2022-02-02T00:00:00Z');
            }
        };

        $data = ['alpha' => 'beta'];
        $result = $this->invokeMethod($transformer, 'modifyForUser', [$data, $object]);

        $this->assertEquals($data, $result);
    }

    protected function invokeMethod($object, $method, array $parameters = [])
    {
        $reflection = new \ReflectionClass($object);
        $method = $reflection->getMethod($method);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }
}
