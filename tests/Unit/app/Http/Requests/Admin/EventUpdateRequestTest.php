<?php

namespace Tests\Unit\app\Http\Requests\Admin;

use App\Http\Requests\Admin\EventUpdateRequest;
use App\Models\Event;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Assert;
use ReflectionObject;
use Tests\TestCase;

class EventUpdateRequestTest extends TestCase
{
    use RefreshDatabase;

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

    public function testNameRuleClosureFailsIfEventNameExists()
    {
        // Create an event with a code matching makePermalink('Test Event') -> 'test-event'
        Event::factory()->create(['code' => 'test-event']);

        $request = new EventUpdateRequest();
        // Ensure we're simulating creation (no existing event)
        $ref = new ReflectionObject($request);
        if ($ref->hasProperty('event')) {
            $p = $ref->getProperty('event');
            $p->setAccessible(true);
            $p->setValue($request, null);
        } else {
            $name = 'event';
            $request->$name = null;
        }

        $rules = $request->rules();
        $closure = null;
        foreach ($rules['name'] as $rule) {
            if ($rule instanceof Closure) {
                $closure = $rule;
                break;
            }
        }
        $called = false;
        $fail = function ($message) use (&$called) {
            $called = true;
            Assert::assertEquals('The event name is already in use', $message);
        };
        // Name that will produce permalink 'test-event'
        $closure('name', 'Test Event', $fail);
        $this->assertTrue($called, 'Fail closure was not called for duplicate event name');
    }

    public function testNameRuleClosurePassesIfEventNameIsUnique()
    {
        $request = new EventUpdateRequest();
        $ref = new ReflectionObject($request);
        if ($ref->hasProperty('event')) {
            $p = $ref->getProperty('event');
            $p->setAccessible(true);
            $p->setValue($request, null);
        } else {
            $name = 'event';
            $request->$name = null;
        }

        $rules = $request->rules();
        $closure = null;
        foreach ($rules['name'] as $rule) {
            if ($rule instanceof Closure) {
                $closure = $rule;
                break;
            }
        }
        $fail = function ($message) {
            Assert::fail('Fail closure should not be called for unique event name');
        };
        // Should not call fail for a unique event name
        $closure('name', 'A Unique Event Name', $fail);
        $this->assertTrue(true); // If no exception, test passes
    }


    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
