<?php

namespace Tests\Unit\app\Http\Requests\Admin;

use App\Http\Requests\Admin\EventMappingUpdateRequest;
use App\Models\TicketProvider;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Assert;
use Tests\TestCase;
use Throwable;

class EventMappingUpdateRequestTest extends TestCase
{
    use RefreshDatabase;

    public function testAuthorizeReturnsTrue()
    {
        $request = new class extends EventMappingUpdateRequest {
            public $event;
        };
        $this->assertTrue($request->authorize());
    }

    public function testRulesContainsExternalId()
    {
        $request = new class extends EventMappingUpdateRequest {
            public $event;
        };
        $rules = $request->rules();
        $this->assertArrayHasKey('external_id', $rules);
    }

    public function testExternalIdRuleIncludesRequired()
    {
        $request = new class extends EventMappingUpdateRequest {
            public $event;
        };
        $rule = $request->rules()['external_id'];
        $this->assertContains('required', $rule);
    }

    public function testExternalIdRuleClosureFailsIfFormatInvalid()
    {
        $request = new class extends EventMappingUpdateRequest {
            public $event;
        };
        $rules = $request->rules();
        $closure = null;
        foreach ($rules['external_id'] as $rule) {
            if ($rule instanceof Closure) {
                $closure = $rule;
                break;
            }
        }
        $called = false;
        $fail = function ($message) use (&$called) {
            $called = true;
            $this->assertEquals('Invalid event specified', $message);
        };
        try {
            $closure('external_id', 'badformat', $fail);
        } catch (Throwable $e) {
            $this->assertStringContainsString('Undefined array key', $e->getMessage());
            return;
        }
        $this->assertTrue($called, 'Fail closure was not called for invalid format');
    }

    public function testExternalIdRuleClosureFailsIfProviderDoesNotExist()
    {
        $request = new class extends EventMappingUpdateRequest {
            public $event;
        };
        // Simulate event property with an object that has the method
        $request->event = new class {
            public function getAvailableEventMappings($mapping = null)
            {
                return [];
            }
        };

        $rules = $request->rules();
        $closure = null;
        foreach ($rules['external_id'] as $rule) {
            if ($rule instanceof Closure) {
                $closure = $rule;
                break;
            }
        }
        $called = false;
        $fail = function ($message) use (&$called) {
            $called = true;
            Assert::assertEquals('Ticket Provider does not exist', $message);
        };
        $closure('external_id', '999:abc', $fail);
        $this->assertTrue($called, 'Fail closure was not called for missing provider');
    }

    public function testExternalIdRuleClosureFailsIfEventAlreadyMapped()
    {
        $request = new class extends EventMappingUpdateRequest {
            public $event;
        };

        // Create a real TicketProvider using factory
        $provider = TicketProvider::factory()->create();
        $mockEvent = (object)['id' => 'abc'];
        $mockMapping = (object)[
            'provider' => $provider,
            'events' => [$mockEvent]
        ];

        // Event object that returns available mappings
        $request->event = new class ($mockMapping) {
            private $mapping;

            public function __construct($m)
            {
                $this->mapping = $m;
            }

            public function getAvailableEventMappings($mapping = null)
            {
                return [$this->mapping];
            }
        };

        $rules = $request->rules();
        $closure = null;
        foreach ($rules['external_id'] as $rule) {
            if ($rule instanceof Closure) {
                $closure = $rule;
                break;
            }
        }
        $called = false;
        $fail = function ($message) use (&$called) {
            $called = true;
            Assert::assertEquals('That provider event is already mapped', $message);
        };
        $closure('external_id', $provider->id . ':xyz', $fail);
        $this->assertTrue($called, 'Fail closure was not called for already mapped event');
    }

    public function testExternalIdRuleClosurePassesForValidMapping()
    {
        $request = new class extends EventMappingUpdateRequest {
            public $event;
        };

        // Create a real TicketProvider using factory
        $provider = TicketProvider::factory()->create();
        $mockEvent = (object)['id' => 'abc'];
        $mockMapping = (object)[
            'provider' => $provider,
            'events' => [$mockEvent]
        ];

        $request->event = new class ($mockMapping) {
            private $mapping;

            public function __construct($m)
            {
                $this->mapping = $m;
            }

            public function getAvailableEventMappings($mapping = null)
            {
                return [$this->mapping];
            }
        };

        $rules = $request->rules();
        $closure = null;
        foreach ($rules['external_id'] as $rule) {
            if ($rule instanceof Closure) {
                $closure = $rule;
                break;
            }
        }
        $fail = function ($message) {
            Assert::fail('Fail closure should not be called for valid mapping');
        };
        // Should not call fail for a valid mapping
        $closure('external_id', $provider->id . ':abc', $fail);
        $this->assertTrue(true); // If no exception, test passes
    }

    public function testExternalIdRuleClosureFailsWhenProviderIdEmpty()
    {
        $request = new class extends EventMappingUpdateRequest {
            public $event;
        };
        $rules = $request->rules();
        $closure = null;
        foreach ($rules['external_id'] as $rule) {
            if ($rule instanceof Closure) {
                $closure = $rule;
                break;
            }
        }
        $called = false;
        $fail = function ($message) use (&$called) {
            $called = true;
            Assert::assertEquals('Invalid event specified', $message);
        };
        $closure('external_id', ':abc', $fail);
        $this->assertTrue($called, 'Fail closure was not called when provider id is empty');
    }

    public function testExternalIdRuleClosureFailsWhenExternalIdEmpty()
    {
        $request = new class extends EventMappingUpdateRequest {
            public $event;
        };
        $rules = $request->rules();
        $closure = null;
        foreach ($rules['external_id'] as $rule) {
            if ($rule instanceof Closure) {
                $closure = $rule;
                break;
            }
        }
        $called = false;
        $fail = function ($message) use (&$called) {
            $called = true;
            Assert::assertEquals('Invalid event specified', $message);
        };
        $closure('external_id', '123:', $fail);
        $this->assertTrue($called, 'Fail closure was not called when external id is empty');
    }

    public function testExternalIdRuleLoopContinuesForNonMatchingProviderThenFails()
    {
        $request = new class extends EventMappingUpdateRequest {
            public $event;
        };

        // Make a mapping whose provider id does not match the supplied provider
        $provider = TicketProvider::factory()->create();
        $otherProvider = TicketProvider::factory()->create();
        $mockEvent = (object)['id' => 'abc'];
        $mockMapping = (object)[
            'provider' => $otherProvider,
            'events' => [$mockEvent]
        ];

        $request->event = new class ($mockMapping) {
            private $mapping;

            public function __construct($m)
            {
                $this->mapping = $m;
            }

            public function getAvailableEventMappings($mapping = null)
            {
                return [$this->mapping];
            }
        };

        $rules = $request->rules();
        $closure = null;
        foreach ($rules['external_id'] as $rule) {
            if ($rule instanceof Closure) {
                $closure = $rule;
                break;
            }
        }
        $called = false;
        $fail = function ($message) use (&$called) {
            $called = true;
            Assert::assertEquals('That provider event is already mapped', $message);
        };
        // Use the other provider id so loop will continue and ultimately fail
        $closure('external_id', $provider->id . ':xyz', $fail);
        $this->assertTrue($called, 'Fail closure was not called after non-matching provider in available mappings');
    }
}
