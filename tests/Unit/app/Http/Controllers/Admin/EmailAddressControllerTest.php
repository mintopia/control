<?php

namespace Tests\Unit\app\Http\Controllers\Admin;

use Tests\TestCase;
use App\Http\Controllers\Admin\EmailAddressController;
use Illuminate\Http\Request;
use Mockery;

class EmailAddressControllerTest extends TestCase
{
    public function testCanInstantiateController()
    {
        $controller = new EmailAddressController();
        $this->assertInstanceOf(EmailAddressController::class, $controller);
    }

    public function testValidateEmailReturnsSuccessResponse()
    {
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('all')->once()->andReturn(['email' => 'test@example.com']);

        $controller = Mockery::mock(EmailAddressController::class, [])->makePartial();

        $controller->shouldReceive('validateEmail')
            ->once()
            ->with(Mockery::type(Request::class))
            ->andReturn(response()->json(['message' => 'Email is valid'], 200));

        $response = $controller->validateEmail($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['message' => 'Email is valid']),
            $response->getContent()
        );
    }

    public function testValidateEmailReturnsErrorForInvalidEmail()
    {
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('all')->once()->andReturn(['email' => 'invalid-email']);

        $controller = Mockery::mock(EmailAddressController::class, [])->makePartial();

        $controller->shouldReceive('validateEmail')
            ->once()
            ->with(Mockery::type(Request::class))
            ->andReturn(response()->json(['error' => 'Invalid email address'], 422));

        $response = $controller->validateEmail($request);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['error' => 'Invalid email address']),
            $response->getContent()
        );
    }

    public function testStoreEmailReturnsSuccessResponse()
    {
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('all')->once()->andReturn(['email' => 'test@example.com']);

        $controller = Mockery::mock(EmailAddressController::class, [])->makePartial();

        $controller->shouldReceive('storeEmail')
            ->once()
            ->with(Mockery::type(Request::class))
            ->andReturn(response()->json(['message' => 'Email stored successfully'], 201));

        $response = $controller->storeEmail($request);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['message' => 'Email stored successfully']),
            $response->getContent()
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
