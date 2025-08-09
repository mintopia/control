<?php

namespace Tests\Unit\app\Http\Requests\Admin;

use Tests\TestCase;
use App\Http\Requests\Admin\TicketTypeMappingUpdateRequest;

class TicketTypeMappingUpdateRequestTest extends TestCase
{
    public function testAuthorizeReturnsTrue()
    {
        $request = new TicketTypeMappingUpdateRequest();
        $this->assertTrue($request->authorize());
    }

    public function testRulesContainsExternalId()
    {
        $request = new TicketTypeMappingUpdateRequest();
        $rules = $request->rules();
        $this->assertArrayHasKey('external_id', $rules);
    }

    public function testRulesReturnsArray()
    {
        $request = new TicketTypeMappingUpdateRequest();
        $rules = $request->rules();
        $this->assertIsArray($rules);
    }

    public function testRulesDoesNotContainUnexpectedFields()
    {
        $request = new TicketTypeMappingUpdateRequest();
        $rules = $request->rules();
        $this->assertCount(1, $rules);
        $this->assertArrayHasKey('external_id', $rules);
    }

    public function testAuthorizeAlwaysTrue()
    {
        $request = new TicketTypeMappingUpdateRequest();
        $this->assertTrue($request->authorize());
    }

    // FIXME: Implement test for external_id rule closure
    // public function testExternalIdRuleClosureFailsIfFormatInvalid()
    // {
    //     $request = new TicketTypeMappingUpdateRequest();
    //     $rules = $request->rules();
    //     $closure = null;
    //     foreach ($rules['external_id'] as $rule) {
    //         if ($rule instanceof \Closure) {
    //             $closure = $rule;
    //             break;
    //         }
    //     }
    //     $called = false;
    //     $fail = function ($message) use (&$called) {
    //         $called = true;
    //         \PHPUnit\Framework\Assert::assertEquals('Invalid ticket type specified', $message);
    //     };
    //     $closure('external_id', 'badformat', $fail);
    //     $this->assertTrue($called, 'Fail closure was not called for invalid format');
    // }

    // public function testExternalIdRuleClosureFailsIfProviderDoesNotExist()
    // {
    //     $request = $this->getMockBuilder(TicketTypeMappingUpdateRequest::class)
    //         ->onlyMethods(['__get'])
    //         ->getMock();
    //     $request->event = (object)[
    //         'getAvailableTicketMappings' => function () {
    //             return [];
    //         }
    //     ];
    //     \Mockery::mock(['alias' => 'App\\Models\\TicketProvider'])
    //         ->shouldReceive('whereId')->with(999)->andReturnSelf()
    //         ->shouldReceive('first')->andReturn(null);

    //     $rules = $request->rules();
    //     $closure = null;
    //     foreach ($rules['external_id'] as $rule) {
    //         if ($rule instanceof \Closure) {
    //             $closure = $rule;
    //             break;
    //         }
    //     }
    //     $called = false;
    //     $fail = function ($message) use (&$called) {
    //         $called = true;
    //         \PHPUnit\Framework\Assert::assertEquals('Ticket Provider does not exist', $message);
    //     };
    //     $closure('external_id', '999:abc', $fail);
    //     $this->assertTrue($called, 'Fail closure was not called for missing provider');
    // }

    // public function testExternalIdRuleClosureFailsIfTypeAlreadyMapped()
    // {
    //     $request = $this->getMockBuilder(TicketTypeMappingUpdateRequest::class)
    //         ->onlyMethods(['__get'])
    //         ->getMock();
    //     $mockProvider = (object)['id' => 1];
    //     $mockType = (object)['id' => 'abc'];
    //     $mockMapping = (object)[
    //         'provider' => $mockProvider,
    //         'types' => [$mockType]
    //     ];
    //     $request->event = (object)[
    //         'getAvailableTicketMappings' => function () use ($mockMapping) {
    //             return [$mockMapping];
    //         }
    //     ];
    //     \Mockery::mock(['alias' => 'App\\Models\\TicketProvider'])
    //         ->shouldReceive('whereId')->with(1)->andReturnSelf()
    //         ->shouldReceive('first')->andReturn($mockProvider);

    //     $rules = $request->rules();
    //     $closure = null;
    //     foreach ($rules['external_id'] as $rule) {
    //         if ($rule instanceof \Closure) {
    //             $closure = $rule;
    //             break;
    //         }
    //     }
    //     $called = false;
    //     $fail = function ($message) use (&$called) {
    //         $called = true;
    //         \PHPUnit\Framework\Assert::assertEquals('That provider ticket type is already mapped', $message);
    //     };
    //     $closure('external_id', '1:xyz', $fail);
    //     $this->assertTrue($called, 'Fail closure was not called for already mapped type');
    // }

    // public function testExternalIdRuleClosurePassesForValidMapping()
    // {
    //     $request = $this->getMockBuilder(TicketTypeMappingUpdateRequest::class)
    //         ->onlyMethods(['__get'])
    //         ->getMock();
    //     $mockProvider = (object)['id' => 1];
    //     $mockType = (object)['id' => 'abc'];
    //     $mockMapping = (object)[
    //         'provider' => $mockProvider,
    //         'types' => [$mockType]
    //     ];
    //     $request->event = (object)[
    //         'getAvailableTicketMappings' => function () use ($mockMapping) {
    //             return [$mockMapping];
    //         }
    //     ];
    //     \Mockery::mock(['alias' => 'App\\Models\\TicketProvider'])
    //         ->shouldReceive('whereId')->with(1)->andReturnSelf()
    //         ->shouldReceive('first')->andReturn($mockProvider);

    //     $rules = $request->rules();
    //     $closure = null;
    //     foreach ($rules['external_id'] as $rule) {
    //         if ($rule instanceof \Closure) {
    //             $closure = $rule;
    //             break;
    //         }
    //     }
    //     $fail = function ($message) {
    //         \PHPUnit\Framework\Assert::fail('Fail closure should not be called for valid mapping');
    //     };
    //     // Should not call fail for a valid mapping
    //     $closure('external_id', '1:abc', $fail);
    //     $this->assertTrue(true); // If no exception, test passes
    // }
}
