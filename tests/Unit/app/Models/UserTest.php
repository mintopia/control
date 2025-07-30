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
}
