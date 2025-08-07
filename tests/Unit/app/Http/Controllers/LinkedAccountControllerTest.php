<?php

namespace Tests\Unit\app\Http\Controllers;

use Tests\TestCase;
use App\Http\Controllers\LinkedAccountController;
use App\Models\SocialProvider;
use App\Exceptions\SocialProviderException;

class LinkedAccountControllerTest extends TestCase
{
    public function testCanInstantiateController()
    {
        $controller = new LinkedAccountController();
        $this->assertInstanceOf(LinkedAccountController::class, $controller);
    }

    public function testCreateRedirectsToProvider()
    {
        $provider = new class extends SocialProvider {
            public function redirect(?string $redirectUrl = null)
            {
                return 'redirected';
            }
        };
        $controller = new LinkedAccountController();
        $result = $controller->create($provider);
        $this->assertEquals('redirected', $result);
    }

    public function testStoreLinksAccountSuccessfully()
    {
        $provider = new class extends SocialProvider {
            public $name = 'test';
            public function user(?string $redirectUrl = null)
            {
                return true;
            }
        };
        $controller = new LinkedAccountController();
        $response = $controller->store($provider);
        $this->assertTrue(method_exists($response, 'getTargetUrl'));
    }

    public function testStoreHandlesException()
    {
        $provider = new class extends SocialProvider {
            public $name = 'test';
            public function user(?string $redirectUrl = null)
            {
                throw new SocialProviderException('fail');
            }
        };
        $controller = new LinkedAccountController();
        $response = $controller->store($provider);
        $this->assertTrue(method_exists($response, 'getTargetUrl'));
    }
}
