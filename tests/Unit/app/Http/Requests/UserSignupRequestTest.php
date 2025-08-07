<?php

namespace Tests\Unit\app\Http\Requests;

use Tests\TestCase;
use App\Http\Requests\UserSignupRequest;

class UserSignupRequestTest extends TestCase
{
    public function testAuthorizeReturnsTrue()
    {
        $request = new UserSignupRequest();
        $this->assertTrue($request->authorize());
    }

    public function testRulesReturnsArray()
    {
        $request = new UserSignupRequest();
        $this->assertIsArray($request->rules());
    }

    public function testMessagesReturnsCombinedMessageIfTermsAndPrivacySet()
    {
        \App\Models\Setting::shouldReceive('fetch')
            ->with('terms')->andReturn(true);
        \App\Models\Setting::shouldReceive('fetch')
            ->with('privacypolicy')->andReturn(true);
        $request = new UserSignupRequest();
        $messages = $request->messages();
        $this->assertArrayHasKey('terms.accepted', $messages);
        $this->assertStringContainsString('Terms and Conditions', $messages['terms.accepted']);
        $this->assertStringContainsString('Privacy Policy', $messages['terms.accepted']);
    }

    public function testMessagesReturnsMessageIfOnlyTermsSet()
    {
        \App\Models\Setting::shouldReceive('fetch')
            ->with('terms')->andReturn(true);
        \App\Models\Setting::shouldReceive('fetch')
            ->with('privacypolicy')->andReturn(false);
        $request = new UserSignupRequest();
        $messages = $request->messages();
        $this->assertArrayHasKey('terms.accepted', $messages);
        $this->assertStringContainsString('Terms and Conditions', $messages['terms.accepted']);
    }

    public function testMessagesReturnsMessageIfOnlyPrivacySet()
    {
        \App\Models\Setting::shouldReceive('fetch')
            ->with('terms')->andReturn(false);
        \App\Models\Setting::shouldReceive('fetch')
            ->with('privacypolicy')->andReturn(true);
        $request = new UserSignupRequest();
        $messages = $request->messages();
        $this->assertArrayHasKey('terms.accepted', $messages);
        $this->assertStringContainsString('Privacy Policy', $messages['terms.accepted']);
    }

    public function testMessagesReturnsEmptyArrayIfNeitherSet()
    {
        \App\Models\Setting::shouldReceive('fetch')
            ->with('terms')->andReturn(false);
        \App\Models\Setting::shouldReceive('fetch')
            ->with('privacypolicy')->andReturn(false);
        $request = new UserSignupRequest();
        $messages = $request->messages();
        $this->assertEquals([], $messages);
    }

    public function testRulesIncludesTermsIfTermsOrPrivacySet()
    {
        \App\Models\Setting::shouldReceive('fetch')
            ->with('terms')->andReturn(true);
        \App\Models\Setting::shouldReceive('fetch')
            ->with('privacypolicy')->andReturn(false);
        $request = $this->getMockBuilder(UserSignupRequest::class)
            ->onlyMethods(['user'])
            ->getMock();
        $request->expects($this->any())
            ->method('user')
            ->willReturn((object)['id' => 1]);
        $rules = $request->rules();
        $this->assertArrayHasKey('terms', $rules);
    }

    public function testRulesDoesNotIncludeTermsIfNeitherSet()
    {
        \App\Models\Setting::shouldReceive('fetch')
            ->with('terms')->andReturn(false);
        \App\Models\Setting::shouldReceive('fetch')
            ->with('privacypolicy')->andReturn(false);
        $request = $this->getMockBuilder(UserSignupRequest::class)
            ->onlyMethods(['user'])
            ->getMock();
        $request->expects($this->any())
            ->method('user')
            ->willReturn((object)['id' => 1]);
        $rules = $request->rules();
        $this->assertArrayNotHasKey('terms', $rules);
    }
}
