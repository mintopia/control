<?php

namespace Tests\Unit\app\Http\Requests\Admin;

use Tests\TestCase;
use App\Http\Requests\Admin\TicketProviderUpdateRequest;

class TicketProviderUpdateRequestStub extends TicketProviderUpdateRequest
{
    public $provider;
}

class TicketProviderUpdateRequestTest extends TestCase
{
    public function testAuthorizeReturnsTrue()
    {
        $request = new TicketProviderUpdateRequest();
        $this->assertTrue($request->authorize());
    }

    public function testRulesIsArray()
    {
        $request = new TicketProviderUpdateRequestStub();
        $request->provider = (object)['settings' => []];
        $rules = $request->rules();
        $this->assertIsArray($rules);
    }

    public function testRulesContainExpectedKeys()
    {
        $request = new TicketProviderUpdateRequestStub();
        $request->provider = (object)['settings' => [
            (object)['code' => 'name', 'validation' => 'required|string'],
            (object)['code' => 'provider', 'validation' => 'required|string']
        ]];
        $rules = $request->rules();
        $expected = ['name', 'provider'];
        foreach ($expected as $key) {
            $this->assertArrayHasKey($key, $rules);
        }
    }

    public function testRulesContainValidationStrings()
    {
        $request = new TicketProviderUpdateRequestStub();
        $request->provider = (object)['settings' => [
            (object)['code' => 'name', 'validation' => 'required|string'],
            (object)['code' => 'provider', 'validation' => 'required|string']
        ]];
        $rules = $request->rules();
        $this->assertStringContainsString('required', $rules['name']);
        $this->assertStringContainsString('string', $rules['name']);
        $this->assertStringContainsString('required', $rules['provider']);
        $this->assertStringContainsString('string', $rules['provider']);
    }

    public function testRulesDoesNotContainUnexpectedFields()
    {
        $request = new TicketProviderUpdateRequestStub();
        $request->provider = (object)['settings' => [
            (object)['code' => 'name', 'validation' => 'required|string'],
            (object)['code' => 'provider', 'validation' => 'required|string']
        ]];
        $rules = $request->rules();
        $allowed = ['enabled', 'name', 'provider'];
        foreach (array_keys($rules) as $key) {
            $this->assertContains($key, $allowed);
        }
    }

    public function testAuthorizeAlwaysTrue()
    {
        $request = new TicketProviderUpdateRequestStub();
        $this->assertTrue($request->authorize());
    }
}
