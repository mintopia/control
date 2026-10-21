<?php

namespace Tests\Unit\app\Models;

use App\Models\Role;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Tests\TestCase;

class RoleTest extends TestCase
{
    public function testCanInstantiateRole()
    {
        $role = new Role();
        $this->assertInstanceOf(Role::class, $role);
    }

    public function testUsersRelationshipIsBelongsToMany()
    {
        $role = new Role();
        $this->assertInstanceOf(BelongsToMany::class, $role->users());
    }

    public function testProtectedFunctionToStringName()
    {
        $role = new Role();
        $role->code = 'ROLE-1';

        $reflection = new \ReflectionClass($role);
        $method = $reflection->getMethod('toStringName');
        $method->setAccessible(true);
        $result = $method->invoke($role);

        $this->assertEquals($role->code, $result);
    }
}
