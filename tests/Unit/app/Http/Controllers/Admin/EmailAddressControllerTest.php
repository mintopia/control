<?php

namespace Tests\Unit\app\Http\Controllers\Admin;

use Tests\TestCase;
use App\Http\Controllers\Admin\EmailAddressController;

class EmailAddressControllerTest extends TestCase
{
    public function testCanInstantiateController()
    {
        $controller = new EmailAddressController();
        $this->assertInstanceOf(EmailAddressController::class, $controller);
    }

    public function testStoreFailsForStaticResponseOrRoute()
    {
        $this->fail('Static method mocking for response() or Route facade is not supported in this environment.');
    }

    public function testUpdateFailsForStaticResponseOrRoute()
    {
        $this->fail('Static method mocking for response() or Route facade is not supported in this environment.');
    }

    public function testDestroyFailsForStaticResponseOrRoute()
    {
        $this->fail('Static method mocking for response() or Route facade is not supported in this environment.');
    }
}
