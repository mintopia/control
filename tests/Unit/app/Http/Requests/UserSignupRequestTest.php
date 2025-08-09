<?php

namespace Tests\Unit\app\Http\Requests;

use Tests\TestCase;
use App\Http\Requests\UserSignupRequest;

class UserSignupRequestTest extends TestCase
{
    //FIXME See tests/SettingsTestabilityRefactor.md for details
    protected $settingMock;

    // protected function setUp(): void
    // {
    //     parent::setUp();
    //     \Mockery::close();
    //     $this->settingMock = \Mockery::mock('overload:App\\Models\\Setting');
    // }
    public function testAuthorizeReturnsTrue()
    {
        $request = new UserSignupRequest();
        $this->assertTrue($request->authorize());
    }

    // public function testRulesReturnsArray()
    // {
    //     // Mock user() to avoid null user error
    //     $request = $this->getMockBuilder(UserSignupRequest::class)
    //         ->onlyMethods(['user'])
    //         ->getMock();
    //     $request->expects($this->any())
    //         ->method('user')
    //         ->willReturn((object)['id' => 1]);
    //     $this->assertIsArray($request->rules());
    // }

    // public function testMessagesReturnsCombinedMessageIfTermsAndPrivacySet()
    // {
    //     $this->settingMock->shouldReceive('fetch')->with('terms')->andReturn(true);
    //     $this->settingMock->shouldReceive('fetch')->with('privacypolicy')->andReturn(true);
    //     $request = new UserSignupRequest();
    //     $messages = $request->messages();
    //     $this->assertArrayHasKey('terms.accepted', $messages);
    //     $this->assertStringContainsString('Terms and Conditions', $messages['terms.accepted']);
    //     $this->assertStringContainsString('Privacy Policy', $messages['terms.accepted']);
    // }

    // public function testMessagesReturnsMessageIfOnlyTermsSet()
    // {
    //     $this->settingMock->shouldReceive('fetch')->with('terms')->andReturn(true);
    //     $this->settingMock->shouldReceive('fetch')->with('privacypolicy')->andReturn(false);
    //     $request = new UserSignupRequest();
    //     $messages = $request->messages();
    //     $this->assertArrayHasKey('terms.accepted', $messages);
    //     $this->assertStringContainsString('Terms and Conditions', $messages['terms.accepted']);
    // }

    // public function testMessagesReturnsMessageIfOnlyPrivacySet()
    // {
    //     $this->settingMock->shouldReceive('fetch')->with('terms')->andReturn(false);
    //     $this->settingMock->shouldReceive('fetch')->with('privacypolicy')->andReturn(true);
    //     $request = new UserSignupRequest();
    //     $messages = $request->messages();
    //     $this->assertArrayHasKey('terms.accepted', $messages);
    //     $this->assertStringContainsString('Privacy Policy', $messages['terms.accepted']);
    // }

    // public function testMessagesReturnsEmptyArrayIfNeitherSet()
    // {
    //     $this->settingMock->shouldReceive('fetch')->with('terms')->andReturn(false);
    //     $this->settingMock->shouldReceive('fetch')->with('privacypolicy')->andReturn(false);
    //     $request = new UserSignupRequest();
    //     $messages = $request->messages();
    //     $this->assertEquals([], $messages);
    // }

    // public function testRulesIncludesTermsIfTermsOrPrivacySet()
    // {
    //     $this->settingMock->shouldReceive('fetch')->with('terms')->andReturn(true);
    //     $this->settingMock->shouldReceive('fetch')->with('privacypolicy')->andReturn(false);
    //     $request = $this->getMockBuilder(UserSignupRequest::class)
    //         ->onlyMethods(['user'])
    //         ->getMock();
    //     $request->expects($this->any())
    //         ->method('user')
    //         ->willReturn((object)['id' => 1]);
    //     $rules = $request->rules();
    //     $this->assertArrayHasKey('terms', $rules);
    // }

    // public function testRulesDoesNotIncludeTermsIfNeitherSet()
    // {
    //     $this->settingMock->shouldReceive('fetch')->with('terms')->andReturn(false);
    //     $this->settingMock->shouldReceive('fetch')->with('privacypolicy')->andReturn(false);
    //     $request = $this->getMockBuilder(UserSignupRequest::class)
    //         ->onlyMethods(['user'])
    //         ->getMock();
    //     $request->expects($this->any())
    //         ->method('user')
    //         ->willReturn((object)['id' => 1]);
    //     $rules = $request->rules();
    //     $this->assertArrayNotHasKey('terms', $rules);
    // }
}
