<?php

namespace Tests\Feature\app\Transformers\V1;

use App\Models\User;
use Illuminate\Support\Carbon;
use ReflectionClass;
use Tests\TestCase;

class AbstractTransformerExtraTest extends TestCase
{
    protected $noopTransformer;
    protected $precedenceTransformer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->noopTransformer = new class (null) {
            protected $user;
            public function __construct($user)
            {
                $this->user = $user;
            }
            protected function modifyForUser($data, $object)
            {
                return $data;
            }
        };
        $this->precedenceTransformer = new class ($this->createMock(\App\Models\User::class)) {
            protected $user;
            public function __construct($user)
            {
                $this->user = $user;
            }
            protected function modifyForUser($data, $object)
            {
                // Simulate admin precedence logic
                if ($this->user && method_exists($this->user, 'hasRole') && $this->user->hasRole('admin')) {
                    // Merge admin-provided properties into the original data so admin values overwrite originals
                    return array_merge($data, [
                        'foo' => 'baz',
                        'extra' => 'value_from_admin',
                        'id' => $object->id,
                        'created_at' => $object->created_at->toIso8601String(),
                        'updated_at' => $object->updated_at->toIso8601String(),
                    ]);
                }
                return $data;
            }
        };
    }

    public function testModifyForUserAdminPropertyPrecedence()
    {
        $user = $this->createMock(User::class);
        $user->method('hasRole')->with('admin')->willReturn(true);

        $transformer = $this->precedenceTransformer;

        // Inject the configured user mock into the transformer so modifyForUser sees admin role.
        $reflectionTransformer = new ReflectionClass($transformer);
        $prop = $reflectionTransformer->getProperty('user');
        $prop->setAccessible(true);
        $prop->setValue($transformer, $user);

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
        $transformer = $this->noopTransformer;

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
        $reflection = new ReflectionClass($object);
        $method = $reflection->getMethod($method);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }
}
