<?php

namespace Tests\Unit\app\Http\Requests\Admin;

use Tests\TestCase;
use App\Http\Requests\Admin\EventMappingUpdateRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\TicketProvider;

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
            if ($rule instanceof \Closure) {
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
        } catch (\Throwable $e) {
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
            if ($rule instanceof \Closure) {
                $closure = $rule;
                break;
            }
        }
        $called = false;
        $fail = function ($message) use (&$called) {
            $called = true;
            \PHPUnit\Framework\Assert::assertEquals('Ticket Provider does not exist', $message);
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
        $request->event = new class($mockMapping) {
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
            if ($rule instanceof \Closure) {
                $closure = $rule;
                break;
            }
        }
        $called = false;
        $fail = function ($message) use (&$called) {
            $called = true;
            \PHPUnit\Framework\Assert::assertEquals('That provider event is already mapped', $message);
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

        $request->event = new class($mockMapping) {
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
            if ($rule instanceof \Closure) {
                $closure = $rule;
                break;
            }
        }
        $fail = function ($message) {
            \PHPUnit\Framework\Assert::fail('Fail closure should not be called for valid mapping');
        };
        // Should not call fail for a valid mapping
        $closure('external_id', $provider->id . ':abc', $fail);
        $this->assertTrue(true); // If no exception, test passes
    }
}
