<?php

namespace Tests\Unit\app\Models;

use Tests\TestCase;
use App\Models\ClanRole;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DummyClanRole extends ClanRole
{
    public function toStringNamePublic(): string
    {
        return $this->toStringName();
    }
}

class ClanRoleTest extends TestCase
{
    public function testCanInstantiateClanRole()
    {
        $role = new ClanRole();
        $this->assertInstanceOf(ClanRole::class, $role);
    }

    public function testMembersRelationshipIsHasMany()
    {
        $role = new ClanRole();
        $this->assertInstanceOf(HasMany::class, $role->members());
    }

    public function testProtectedToStringNameReturnsCode()
    {
        $dummy = new DummyClanRole();
        $dummy->code = 'leader';
        $this->assertEquals('leader', $dummy->toStringNamePublic());
    }
}
