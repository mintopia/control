<?php

namespace Tests\Unit\app\Http\Requests;

use App\Http\Requests\ClanMembershipRequest;
use App\Http\Requests\ClanRequest;
use App\Models\Clan;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use function App\makePermalink;

class ClanRequestTest extends TestCase
{
    use RefreshDatabase;

    public function testAuthorizeReturnsTrue()
    {
        $request = new ClanRequest();
        $this->assertTrue($request->authorize());
    }

    public function testRulesReturnsArray()
    {
        $request = new ClanRequest();
        $this->assertIsArray($request->rules());
    }

    public function testRulesContainNameWithRequiredStringMinMax()
    {
        $request = new ClanRequest();
        $rules = $request->rules();
        $this->assertArrayHasKey('name', $rules);
        $nameRules = $rules['name'];
        $this->assertContains('required', $nameRules);
        $this->assertContains('string', $nameRules);
        $this->assertContains('min:3', $nameRules);
        $this->assertContains('max:100', $nameRules);
    }

    public function testNameRuleClosureFailsIfPermalinkEmpty()
    {
        // Mock makePermalink to return empty string
        ClanRequest::macro('makePermalink', function ($value) {
            return '';
        });
        $request = new ClanRequest();
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
            $this->assertEquals('The clan name is not valid', $message);
        };
        $closure->call($request, 'name', '!!!', $fail);
        $this->assertTrue($called, 'Fail closure was not called for empty permalink');
    }

    public function testNameRuleClosureFailsWhenPermalinkExists()
    {
        // Use a simple, predictable name that slugifies directly
        $name = 'my-clan';
        $permalink = makePermalink($name);
        // Create a clan with the intended name so the observer will set the code
        $clan = Clan::factory()->create(['name' => $name]);
        // Some factories may not persist custom attributes as expected; ensure code is set
        $clan->code = $permalink;
        $clan->save();

        // Ensure the clan exists in the database with the expected code
        $this->assertEquals(1, Clan::count(), 'Expected exactly one clan in DB after factory create');
        $actual = Clan::first()->code;
        $this->assertEquals($permalink, $actual, "Created clan code did not match expected permalink (got: {$actual})");

        $request = new ClanRequest();
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
            $this->assertEquals('That clan name is not available', $message);
        };
        $closure->call($request, 'name', $name, $fail);
        $this->assertTrue($called, 'Fail closure was not called for existing permalink');
    }

    public function testNameRuleClosurePassesWhenEditingOwnClan()
    {
        $name = 'own-clan';
        $permalink = makePermalink($name);
        $clan = Clan::factory()->create(['name' => $name]);
        // Observer will set the code based on the name on save
        $clan->refresh();

        $request = new ClanRequest();
        // Simulate editing the same clan by attaching it to the request
        $request->clan = $clan;

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
        };
        $closure->call($request, 'name', $name, $fail);
        $this->assertFalse($called, 'Fail closure was called when editing own clan');
    }

    public function testRulesContainCodeRuleClosure()
    {
        $request = new ClanMembershipRequest();
        $rules = $request->rules();
        $closureFound = false;
        foreach ($rules['code'] as $rule) {
            if ($rule instanceof Closure) {
                $closureFound = true;
                break;
            }
        }
        $this->assertTrue($closureFound, 'Closure rule not found for code');
    }

    public function testCodeRuleClosureFailsWithEmptyValue()
    {
        // No clans in DB -> empty code should fail
        $request = new ClanMembershipRequest();
        $rules = $request->rules();
        $closure = null;
        foreach ($rules['code'] as $rule) {
            if ($rule instanceof Closure) {
                $closure = $rule;
                break;
            }
        }
        $called = false;
        $fail = function ($message) use (&$called) {
            $called = true;
            $this->assertEquals('The invite code is invalid', $message);
        };
        $closure('code', '', $fail);
        $this->assertTrue($called, 'Fail closure was not called for empty code');
    }

    public function testCodeRuleClosureIsCaseInsensitive()
    {
        // Create a clan with invite_code ABCDEF
        Clan::factory()->create(['invite_code' => 'ABCDEF']);
        $request = new ClanMembershipRequest();
        $rules = $request->rules();
        $closure = null;
        foreach ($rules['code'] as $rule) {
            if ($rule instanceof Closure) {
                $closure = $rule;
                break;
            }
        }
        $called = false;
        $fail = function () use (&$called) {
            $called = true;
        };
        $closure('code', 'abcdef', $fail);
        $this->assertFalse($called, 'Fail closure was called for valid code (case-insensitive check failed)');
    }

    public function testRulesArrayStructure()
    {
        $request = new ClanMembershipRequest();
        $rules = $request->rules();
        $this->assertIsArray($rules);
        $this->assertArrayHasKey('code', $rules);
        $this->assertIsArray($rules['code']);
        $this->assertContains('required', $rules['code']);
        $this->assertContains('string', $rules['code']);
    }
}
