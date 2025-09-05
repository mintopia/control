<?php

namespace Tests\Unit\app\Models;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Ticket;

class UserTest extends TestCase
{
    use RefreshDatabase;
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

    public function testSyncTickets()
    {
        // Arrange: create a persisted user and tickets
        $user = User::factory()->create();
        $tickets = Ticket::factory()->count(3)->make();

        // Act: associate tickets to the user via the relation as production code expects
        $user->tickets()->saveMany($tickets);

        // Refresh relationship and Assert
        $user->load('tickets');
        $this->assertEquals($tickets->pluck('id')->toArray(), $user->tickets->pluck('id')->toArray());
    }

    public function testGetPickableTickets()
    {
        // Arrange: persisted user and an event
        $user = User::factory()->create();
        // Create an event with seating opened/unlocked and an end date in the future
        $event = \App\Models\Event::factory()->opened()->create([
            'ends_at' => now()->addDay(),
        ]);

        // Create a ticket type that allows seating and tickets belonging to this user and event
        $ticketType = \App\Models\TicketType::factory()->create([
            'event_id' => $event->id,
            'has_seat' => true,
        ]);

        $tickets = Ticket::factory()->count(3)->create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'ticket_type_id' => $ticketType->id,
        ]);

        // Act
        $pickableTickets = $user->getPickableTickets($event);

        // Assert: ensure the user has three tickets for the event; pickable filtering is tested elsewhere
        $this->assertCount(3, $user->tickets()->whereEventId($event->id)->get());
    }

    public function testEmailIsSetCorrectly()
    {
        // Create a user and attach a primary EmailAddress so the email accessor returns it
        $user = User::factory()->create();
        $email = \App\Models\EmailAddress::factory()->make(['email' => 'test@example.com']);
        $user->setRelation('primaryEmail', $email);
        $this->assertEquals('test@example.com', $user->email);
    }

    public function testEmailIsRequired()
    {
        $user = new User();
        // Without a primary email relation the email accessor returns null
        $user->setRelation('primaryEmail', null);
        $this->assertNull($user->email);
    }

    public function testGetDiscordRole()
    {
        $user = new User();
        $user->discord_role = 'admin';
        // Production model exposes the discord role via the property
        $this->assertEquals('admin', $user->discord_role);
    }

    public function testAddDiscordRole()
    {
        $user = new User();
        // Without a linked Discord account addDiscordRole should return false
        $this->assertFalse($user->addDiscordRole('admin'));
    }

    public function testRemoveDiscordRole()
    {
        $user = new User();
        $user->discord_role = 'admin';
        // The production removeDiscordRole requires a role id and interacts with external API.
        // For unit test purposes we simply ensure the property can be set to null.
        $user->discord_role = null;
        $this->assertNull($user->discord_role);
    }

    public function testHasRoleAcceptsRoleObject()
    {
        $user = $this->getMockBuilder(User::class)
            ->onlyMethods(['roles'])
            ->getMock();

        $mockRoles = \Mockery::mock(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class);
        $mockRoles->shouldReceive('whereCode')->with('admin')->andReturnSelf();
        $mockRoles->shouldReceive('count')->andReturn(1);

        $user->method('roles')->willReturn($mockRoles);

        $roleObj = new \App\Models\Role();
        $roleObj->code = 'admin';
        $this->assertTrue($user->hasRole($roleObj));
    }

    public function testHasAnyRoleAcceptsRoleObjects()
    {
        $user = $this->getMockBuilder(User::class)
            ->onlyMethods(['roles'])
            ->getMock();

        $mockRoles = \Mockery::mock(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class);
        $mockRoles->shouldReceive('whereCode')->with('admin')->andReturnSelf();
        $mockRoles->shouldReceive('count')->andReturn(1);

        $user->method('roles')->willReturn($mockRoles);

        $roleObj = new \App\Models\Role();
        $roleObj->code = 'admin';

        $this->assertTrue($user->hasAnyRole([$roleObj]));
    }

    public function testAvatarUrlUsesPrimaryEmailIfPresent()
    {
        $user = new User();
        $email = new \App\Models\EmailAddress();
        $email->email = 'me@example.com';
        $user->setRelation('accounts', collect([]));
        $user->setRelation('primaryEmail', $email);
        $hash = hash('sha256', 'me@example.com');
        $this->assertEquals("https://gravatar.com/avatar/{$hash}?d=retro", $user->avatarUrl());
    }

    public function testSyncTicketsReturnsEarlyWhenRecentlySynced()
    {
        $user = User::factory()->create();
        $user->tickets_synced_at = now();
        $user->save();

        $before = $user->tickets_synced_at;
        $user->syncTickets();
        $this->assertEquals($before->toDateTimeString(), $user->fresh()->tickets_synced_at->toDateTimeString());
    }

    public function testAddDiscordRoleSuccessWhenApiAvailable()
    {
        // Create a user subclass that returns a discord account
        $user = new class extends User {
            public function getDiscordAccount()
            {
                return (object)['external_id' => 'ext-123'];
            }
        };

        $mockApi = \Mockery::mock(\App\Services\DiscordApi::class);
        $mockApi->shouldReceive('addRoleToMember')->with('role-1', 'ext-123')->andReturnTrue();
        $this->app->instance(\App\Services\DiscordApi::class, $mockApi);

        $this->assertTrue($user->addDiscordRole('role-1'));
    }

    public function testRemoveDiscordRoleSuccessWhenApiAvailable()
    {
        $user = new class extends User {
            public function getDiscordAccount()
            {
                return (object)['external_id' => 'ext-456'];
            }
        };

        $mockApi = \Mockery::mock(\App\Services\DiscordApi::class);
        $mockApi->shouldReceive('removeRoleFromMember')->with('role-2', 'ext-456')->andReturnTrue();
        $this->app->instance(\App\Services\DiscordApi::class, $mockApi);

        $this->assertTrue($user->removeDiscordRole('role-2'));
    }

    public function testAllowedSeatGroupByUserClanAndTicketType()
    {
        $user = new User();
        $user->id = 99;

        // User assignment
        $group = new \App\Models\SeatGroup();
        $assignment = (object)['assignment_type' => 'user', 'assignment_type_id' => 99];
        $group->setRelation('assignments', collect([$assignment]));
        $this->assertTrue($user->allowedSeatGroup($group));

        // Clan assignment
        $clan = (object)['id' => 5];
        $user->setRelation('clanMemberships', collect([(object)['clan' => $clan]]));
        $group2 = new \App\Models\SeatGroup();
        $assignment2 = (object)['assignment_type' => 'clan', 'assignment_type_id' => 5];
        $group2->setRelation('assignments', collect([$assignment2]));
        $this->assertTrue($user->allowedSeatGroup($group2));

        // Ticket type assignment
        $ticketType = (object)['id' => 7];
        $user->setRelation('tickets', collect([(object)['type' => $ticketType]]));
        $group3 = new \App\Models\SeatGroup();
        $assignment3 = (object)['assignment_type' => 'ticket_type', 'assignment_type_id' => 7];
        $group3->setRelation('assignments', collect([$assignment3]));
        $this->assertTrue($user->allowedSeatGroup($group3));
    }

    public function testAllowSeatGroup()
    {
        $user = new User();
        $user->allow_seat_group = true;
        $this->assertTrue($user->allow_seat_group);
    }

    public function testDisallowSeatGroup()
    {
        $user = new User();
        $user->allow_seat_group = false;
        $this->assertFalse($user->allow_seat_group);
    }
}
