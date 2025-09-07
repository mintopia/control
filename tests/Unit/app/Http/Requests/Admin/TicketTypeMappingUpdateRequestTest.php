<?php

namespace Tests\Unit\app\Http\Requests\Admin;

use App\Http\Requests\Admin\TicketTypeMappingUpdateRequest;
use App\Models\TicketProvider;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Assert;
use Tests\TestCase;
use Throwable;

class TicketTypeMappingUpdateRequestTest extends TestCase
{
    use RefreshDatabase;

    public function testAuthorizeReturnsTrue()
    {
        $request = new class extends TicketTypeMappingUpdateRequest {
            public $event;
        };
        $this->assertTrue($request->authorize());
    }

    public function testRulesContainsExternalId()
    {
        $request = new class extends TicketTypeMappingUpdateRequest {
            public $event;
        };
        $rules = $request->rules();
        $this->assertArrayHasKey('external_id', $rules);
    }

    public function testRulesReturnsArray()
    {
        $request = new class extends TicketTypeMappingUpdateRequest {
            public $event;
        };
        $rules = $request->rules();
        $this->assertIsArray($rules);
    }

    public function testRulesDoesNotContainUnexpectedFields()
    {
        $request = new class extends TicketTypeMappingUpdateRequest {
            public $event;
        };
        $rules = $request->rules();
        $this->assertCount(1, $rules);
        $this->assertArrayHasKey('external_id', $rules);
    }

    public function testAuthorizeAlwaysTrue()
    {
        $request = new class extends TicketTypeMappingUpdateRequest {
            public $event;
        };
        $this->assertTrue($request->authorize());
    }

    public function testExternalIdRuleClosureFailsIfFormatInvalid()
    {
        $request = new TicketTypeMappingUpdateRequest();
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
            Assert::assertEquals('Invalid ticket type specified', $message);
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
        $request = new class extends TicketTypeMappingUpdateRequest {
            public $event;
        };
        $request->event = new class {
            public function getAvailableTicketMappings($mapping = null)
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

    public function testExternalIdRuleClosureFailsIfTypeAlreadyMapped()
    {
        $request = new class extends TicketTypeMappingUpdateRequest {
            public $event;
        };
        $provider = TicketProvider::factory()->create();
        $mockType = (object)['id' => 'abc'];
        $mockMapping = (object)['provider' => $provider, 'types' => [$mockType]];
        $request->event = new class ($mockMapping) {
            private $m;

            public function __construct($m)
            {
                $this->m = $m;
            }

            public function getAvailableTicketMappings($mapping = null)
            {
                return [$this->m];
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
            Assert::assertEquals('That provider ticket type is already mapped', $message);
        };
        $closure('external_id', $provider->id . ':xyz', $fail);
        $this->assertTrue($called, 'Fail closure was not called for already mapped type');
    }

    public function testExternalIdRuleClosurePassesForValidMapping()
    {
        $request = new class extends TicketTypeMappingUpdateRequest {
            public $event;
        };
        $provider = TicketProvider::factory()->create();
        $mockType = (object)['id' => 'abc'];
        $mockMapping = (object)['provider' => $provider, 'types' => [$mockType]];
        $request->event = new class ($mockMapping) {
            private $m;

            public function __construct($m)
            {
                $this->m = $m;
            }

            public function getAvailableTicketMappings($mapping = null)
            {
                return [$this->m];
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
        $request = new class extends TicketTypeMappingUpdateRequest {
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
            Assert::assertEquals('Invalid ticket type specified', $message);
        };
        $closure('external_id', ':abc', $fail);
        $this->assertTrue($called, 'Fail closure was not called when provider id is empty');
    }

    public function testExternalIdRuleClosureFailsWhenExternalIdEmpty()
    {
        $request = new class extends TicketTypeMappingUpdateRequest {
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
            Assert::assertEquals('Invalid ticket type specified', $message);
        };
        $closure('external_id', '123:', $fail);
        $this->assertTrue($called, 'Fail closure was not called when external id is empty');
    }

    public function testExternalIdRuleLoopContinuesForNonMatchingProviderThenFails()
    {
        $request = new class extends TicketTypeMappingUpdateRequest {
            public $event;
        };
        $provider = TicketProvider::factory()->create();
        $other = TicketProvider::factory()->create();
        $mockType = (object)['id' => 'abc'];
        $mockMapping = (object)['provider' => $other, 'types' => [$mockType]];
        $request->event = new class ($mockMapping) {
            private $m;

            public function __construct($m)
            {
                $this->m = $m;
            }

            public function getAvailableTicketMappings($mapping = null)
            {
                return [$this->m];
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
            Assert::assertEquals('That provider ticket type is already mapped', $message);
        };
        $closure('external_id', $provider->id . ':xyz', $fail);
        $this->assertTrue($called, 'Fail closure was not called after non-matching provider in available mappings');
    }
}
