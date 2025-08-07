<?php

namespace Tests\Unit\app\Http\Requests\Admin;

use Tests\TestCase;
use App\Http\Requests\Admin\SeatingPlanUpdateRequest;

class SeatingPlanUpdateRequestTest extends TestCase
{
    public function testAuthorizeReturnsTrue()
    {
        $request = new SeatingPlanUpdateRequest();
        $this->assertTrue($request->authorize());
    }

    public function testRulesContainsName()
    {
        $request = new SeatingPlanUpdateRequest();
        $rules = $request->rules();
        $this->assertArrayHasKey('name', $rules);
    }

    public function testRulesContainAllExpectedKeys()
    {
        $request = new SeatingPlanUpdateRequest();
        $rules = $request->rules();
        $expected = [
            'name',
            'image_url',
            'scale'
        ];
        foreach ($expected as $key) {
            $this->assertArrayHasKey($key, $rules);
        }
    }

    public function testNameRuleIncludesRequiredStringMax()
    {
        $request = new SeatingPlanUpdateRequest();
        $rule = $request->rules()['name'];
        $this->assertContains('required', $rule);
        $this->assertContains('string', $rule);
        $this->assertContains('max:100', $rule);
    }

    public function testNameRuleClosureFailsIfPlanNameInUse()
    {
        $mockPlan = (object)['id' => 2];
        $mockPlans = new class($mockPlan) {
            private $mockPlan;
            public function __construct($mockPlan)
            {
                $this->mockPlan = $mockPlan;
            }
            public function whereCode($code)
            {
                return $this;
            }
            public function first()
            {
                return $this->mockPlan;
            }
        };
        $mockEvent = new class($mockPlans) {
            private $mockPlans;
            public function __construct($mockPlans)
            {
                $this->mockPlans = $mockPlans;
            }
            public function seatingPlans()
            {
                return $this->mockPlans;
            }
        };
        $request = $this->getMockBuilder(SeatingPlanUpdateRequest::class)
            ->onlyMethods([])
            ->getMock();
        $request->event = $mockEvent;
        $request->seatingplan = (object)['id' => 1];
        $rules = $request->rules();
        $closure = null;
        foreach ($rules['name'] as $rule) {
            if ($rule instanceof \Closure) {
                $closure = $rule;
                break;
            }
        }
        $called = false;
        $fail = function ($message) use (&$called) {
            $called = true;
            $this->assertEquals('The seating plan name is already in use', $message);
        };
        $closure('name', 'Test Plan', $fail);
        $this->assertTrue($called, 'Fail closure was not called for duplicate plan name');
    }

    public function testNameRuleClosurePassesIfPlanNameNotInUse()
    {
        $mockPlans = new class {
            public function whereCode($code)
            {
                return $this;
            }
            public function first()
            {
                return null;
            }
        };
        $mockEvent = new class($mockPlans) {
            private $mockPlans;
            public function __construct($mockPlans)
            {
                $this->mockPlans = $mockPlans;
            }
            public function seatingPlans()
            {
                return $this->mockPlans;
            }
        };
        $request = $this->getMockBuilder(SeatingPlanUpdateRequest::class)
            ->onlyMethods([])
            ->getMock();
        $request->event = $mockEvent;
        $request->seatingplan = null;
        $rules = $request->rules();
        $closure = null;
        foreach ($rules['name'] as $rule) {
            if ($rule instanceof \Closure) {
                $closure = $rule;
                break;
            }
        }
        $fail = function () {
            $this->fail('Fail closure should not be called when plan name is not in use');
        };
        $closure('name', 'Test Plan', $fail);
        $this->assertTrue(true, 'Closure did not call fail, as expected');
    }

    public function testImageUrlRuleIncludesSometimesUrlNullable()
    {
        $request = new SeatingPlanUpdateRequest();
        $rule = $request->rules()['image_url'];
        $this->assertStringContainsString('sometimes', $rule);
        $this->assertStringContainsString('url:http,https', $rule);
        $this->assertStringContainsString('nullable', $rule);
    }

    public function testScaleRuleIncludesSometimesIntegerNumericMinMaxNullable()
    {
        $request = new SeatingPlanUpdateRequest();
        $rule = $request->rules()['scale'];
        $this->assertStringContainsString('sometimes', $rule);
        $this->assertStringContainsString('integer', $rule);
        $this->assertStringContainsString('numeric', $rule);
        $this->assertStringContainsString('min:25', $rule);
        $this->assertStringContainsString('max:200', $rule);
        $this->assertStringContainsString('nullable', $rule);
    }
}
