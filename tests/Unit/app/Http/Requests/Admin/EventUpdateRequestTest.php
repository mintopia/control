<?php

namespace Tests\Unit\app\Http\Requests\Admin;

use Tests\TestCase;
use App\Http\Requests\Admin\EventUpdateRequest;
use Mockery;

class EventUpdateRequestTest extends TestCase
{
    public function testAuthorizeReturnsTrue()
    {
        $request = new EventUpdateRequest();
        $this->assertTrue($request->authorize());
    }

    public function testRulesContainsName()
    {
        $request = new EventUpdateRequest();
        $rules = $request->rules();
        $this->assertArrayHasKey('name', $rules);
    }

    public function testRulesContainAllExpectedKeys()
    {
        $request = new EventUpdateRequest();
        $rules = $request->rules();
        $expected = [
            'name',
            'starts_at',
            'ends_at',
            'boxoffice_url',
            'seating_locked',
            'seating_opens_at',
            'seating_closes_at',
            'draft'
        ];
        foreach ($expected as $key) {
            $this->assertArrayHasKey($key, $rules);
        }
    }

    public function testNameRuleIncludesRequiredStringMax()
    {
        $request = new EventUpdateRequest();
        $rule = $request->rules()['name'];
        $this->assertContains('required', $rule);
        $this->assertContains('string', $rule);
        $this->assertContains('max:100', $rule);
    }

    public function testNameRuleClosureFailsIfEventNameInUse()
    {
        // Unable to mock static methods with current Mockery version
        $this->fail('Static method mocking for App\\Models\\Event is not supported in this environment.');
    }

    public function testNameRuleClosurePassesIfEventNameNotInUse()
    {
        // Unable to mock static methods with current Mockery version
        $this->fail('Static method mocking for App\\Models\\Event is not supported in this environment.');
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
