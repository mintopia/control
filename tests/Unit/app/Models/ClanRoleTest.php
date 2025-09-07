<?php

namespace Tests\Unit\app\Models;

use App\Models\ClanRole;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tests\TestCase;

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
        $dummy = new HelperClasses\DummyClanRole();
        $dummy->code = 'leader';
        $this->assertEquals('leader', $dummy->toStringNamePublic());
    }
}
