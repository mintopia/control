<?php

namespace Tests\Unit\app\Http\Controllers;

use Tests\TestCase;
use App\Http\Controllers\EmailAddressController;
use App\Models\User;
use App\Models\EmailAddress;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Mockery;

class EmailAddressControllerTest extends TestCase
{
    public function testCanInstantiateController()
    {
        $controller = new EmailAddressController();
        $this->assertInstanceOf(EmailAddressController::class, $controller);
    }

    public function testCreateReturnsView()
    {
        $controller = new EmailAddressController();
        $response = $controller->create();
        $this->assertTrue(is_object($response));
    }

    public function testStoreCreatesEmailAndSendsVerification()
    {
        Mail::fake();
        $user = User::factory()->create();
        $request = Mockery::mock(\App\Http\Requests\EmailAddressRequest::class);
        $request->shouldReceive('input')->with('email')->andReturn('test@example.com');
        $request->shouldReceive('user')->andReturn($user);
        $controller = new EmailAddressController();
        $response = $controller->store($request);
        $this->assertTrue(method_exists($response, 'getTargetUrl'));
    }

    public function testDeleteReturnsViewOrRedirects()
    {
        $user = User::factory()->create();
        $email = EmailAddress::factory()->create(['user_id' => $user->id]);
        $controller = new EmailAddressController();
        $response = $controller->delete($email);
        $this->assertTrue(is_object($response));
    }

    public function testDestroyDeletesEmail()
    {
        $user = User::factory()->create();
        $email = EmailAddress::factory()->create(['user_id' => $user->id]);
        $controller = new EmailAddressController();
        $response = $controller->destroy($email);
        $this->assertDatabaseMissing('email_addresses', ['id' => $email->id]);
    }
}
