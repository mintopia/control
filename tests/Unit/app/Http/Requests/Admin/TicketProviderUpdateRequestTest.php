<?php

namespace Tests\Unit\app\Http\Requests\Admin;

use App\Http\Requests\Admin\TicketProviderUpdateRequest;
use Tests\TestCase;

class TicketProviderUpdateRequestTest extends TestCase
{
    public function testAuthorizeReturnsTrue()
    {
        $request = new TicketProviderUpdateRequest();
        $this->assertTrue($request->authorize());
    }

    public function testRulesIsArray()
    {
        $request = new HelperClasses\TicketProviderUpdateRequestStub();
        $request->provider = (object)['settings' => []];
        $rules = $request->rules();
        $this->assertIsArray($rules);
    }

    public function testRulesContainExpectedKeys()
    {
        $request = new HelperClasses\TicketProviderUpdateRequestStub();
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
        $request = new HelperClasses\TicketProviderUpdateRequestStub();
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
        $request = new HelperClasses\TicketProviderUpdateRequestStub();
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
        $request = new HelperClasses\TicketProviderUpdateRequestStub();
        $this->assertTrue($request->authorize());
    }
}
