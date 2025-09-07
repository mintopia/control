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

    public function testProtectedToStringNameReturnsCode()
    {
        $dummy = new HelperClasses\DummyRole();
        $dummy->code = 'test-code';
        $this->assertEquals('test-code', $dummy->toStringNamePublic());
    }
}
