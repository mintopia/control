<?php

namespace Tests\Unit\app\Models;

use Tests\TestCase;
use App\Models\Role;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class DummyRole extends Role
{
    public function toStringNamePublic(): string
    {
        return $this->toStringName();
    }
}

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

    public function testProtectedToStringNameReturnsCode()
    {
        $dummy = new DummyRole();
        $dummy->code = 'test-code';
        $this->assertEquals('test-code', $dummy->toStringNamePublic());
    }
}
