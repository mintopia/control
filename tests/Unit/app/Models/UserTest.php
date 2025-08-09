<?php

namespace Tests\Unit\app\Models;

use Tests\TestCase;
use App\Models\User;

class UserTest extends TestCase
{
    public function testCanInstantiateUser()
    {
        $user = new User();
        $this->assertInstanceOf(User::class, $user);
    }

    public function testEmailsRelationship()
    {
        $user = new User();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $user->emails());
    }

    public function testPrimaryEmailRelationship()
    {
        $user = new User();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $user->primaryEmail());
    }

    public function testAccountsRelationship()
    {
        $user = new User();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $user->accounts());
    }

    public function testTicketsRelationship()
    {
        $user = new User();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $user->tickets());
    }

    public function testRolesRelationship()
    {
        $user = new User();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $user->roles());
    }

    public function testClanMembershipsRelationship()
    {
        $user = new User();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $user->clanMemberships());
    }

    public function testHasRoleReturnsTrueIfRoleExists()
    {
        $user = $this->getMockBuilder(User::class)
            ->onlyMethods(['roles'])
            ->getMock();

        $mockRoles = \Mockery::mock(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class);
        $mockRoles->shouldReceive('whereCode')->with('admin')->andReturnSelf();
        $mockRoles->shouldReceive('count')->andReturn(1);

        $user->method('roles')->willReturn($mockRoles);

        $this->assertTrue($user->hasRole('admin'));
    }

    public function testHasRoleReturnsFalseIfRoleNotExists()
    {
        $user = $this->getMockBuilder(User::class)
            ->onlyMethods(['roles'])
            ->getMock();

        $mockRoles = \Mockery::mock(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class);
        $mockRoles->shouldReceive('whereCode')->with('user')->andReturnSelf();
        $mockRoles->shouldReceive('count')->andReturn(0);

        $user->method('roles')->willReturn($mockRoles);

        $this->assertFalse($user->hasRole('user'));
    }

    public function testHasAnyRoleReturnsTrueForFirstMatchingRole()
    {
        $user = $this->getMockBuilder(User::class)
            ->onlyMethods(['roles'])
            ->getMock();

        $mockRoles = \Mockery::mock(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class);

        // Simulate: first role ('admin') exists, so should return true and not check further
        $mockRoles->shouldReceive('whereCode')->with('admin')->once()->andReturnSelf();
        $mockRoles->shouldReceive('count')->once()->andReturn(1);

        // The roles() method will be called for each role checked, but should stop at first match
        $user->method('roles')->willReturn($mockRoles);

        $this->assertTrue($user->hasAnyRole(['admin', 'editor', 'user']));
    }

    public function testHasAnyRoleReturnsFalseIfNoRolesExist()
    {
        $user = $this->getMockBuilder(User::class)
            ->onlyMethods(['roles'])
            ->getMock();

        $mockRoles = \Mockery::mock(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class);
        $mockRoles->shouldReceive('whereCode')->with('editor')->andReturnSelf();
        $mockRoles->shouldReceive('count')->andReturn(0);
        $mockRoles->shouldReceive('whereCode')->with('user')->andReturnSelf();
        $mockRoles->shouldReceive('count')->andReturn(0);

        $user->method('roles')->willReturn($mockRoles);

        $this->assertFalse($user->hasAnyRole(['editor', 'user']));
    }

    public function testAvatarUrlReturnsAccountAvatarIfExists()
    {
        $user = new User();
        $mockAccount = new class {
            public $avatar_url = 'http://avatar.url/img.png';
        };
        $user->setRelation('accounts', collect([$mockAccount]));
        $this->assertEquals('http://avatar.url/img.png', $user->avatarUrl());
    }

    public function testAvatarUrlReturnsGravatarIfNoAccountAvatar()
    {
        $user = new User();
        $user->nickname = 'testuser';
        $user->setRelation('accounts', collect([]));
        $user->setRelation('primaryEmail', null);
        $hash = hash('sha256', 'testuser');
        $this->assertEquals("https://gravatar.com/avatar/{$hash}?d=retro", $user->avatarUrl());
    }

    public function testToStringNameReturnsNickname()
    {
        $user = new User();
        $user->nickname = 'nick';
        $reflection = new \ReflectionClass($user);
        $method = $reflection->getMethod('toStringName');
        $method->setAccessible(true);
        $this->assertEquals('nick', $method->invoke($user));
    }
}
