<?php

namespace Tests\Unit\app\Http\Controllers;

use Tests\TestCase;
use App\Http\Controllers\UserController;

class UserControllerTest extends TestCase
{
    public function testCanInstantiateController()
    {
        $controller = new UserController();
        $this->assertInstanceOf(UserController::class, $controller);
    }

    public function testProfileFailsForStaticSocialProvider()
    {
        $this->fail('Static method mocking for SocialProvider::whereEnabled() is not supported in this environment.');
    }

    public function testLogoutFailsForStaticAuthOrResponse()
    {
        $this->fail('Static method mocking for Auth::logout() or response() is not supported in this environment.');
    }

    public function testLoginRedirectFailsForStaticResponse()
    {
        $this->fail('Static method mocking for response() is not supported in this environment.');
    }

    public function testLoginReturnFailsForStaticAuthOrResponse()
    {
        $this->fail('Static method mocking for Auth::hasUser() or response() is not supported in this environment.');
    }
}
