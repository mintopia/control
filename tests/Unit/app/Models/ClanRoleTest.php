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

    public function testProtectedFunctionToStringName()
    {
        $clanRole = new ClanRole();
        $clanRole->code = 'clanRole-1';

        $reflection = new \ReflectionClass($clanRole);
        $method = $reflection->getMethod('toStringName');
        $method->setAccessible(true);
        $result = $method->invoke($clanRole);

        $this->assertEquals($clanRole->code, $result);
    }
}
