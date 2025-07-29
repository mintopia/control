<?php

namespace Tests\Unit\App\Mail;

use App\Mail\VerifyEmail;
use App\Models\EmailAddress;
use App\Models\Setting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class VerifyEmailTest extends TestCase
{
    // These tests currently do not work, the EmailAddressFactory
    // FIXME: Using the EmailAddress::factory() resulted in a database connection error (User:Factory() needs it..?)
    // TEST: SettingFactory was stood up, but does not work yet...

    public function testEnvelopeContainsCorrectSubjectAndSender()
    {
        $emailAddress = EmailAddress::factory()->make();
        Setting::factory()->create(['code' => 'name', 'value' => 'Test App']);
        Config::set('mail.from.address', 'no-reply@test.com');

        $mailable = new VerifyEmail($emailAddress);
        $envelope = $mailable->envelope();

        $this->assertEquals('Test App - Verify Email Address', $envelope->subject);
        $this->assertEquals('no-reply@test.com', $envelope->from->address);
        $this->assertEquals('Test App', $envelope->from->name);
    }

    public function testContentContainsCorrectData()
    {
        $emailAddress = EmailAddress::factory()->make([
            'id' => 1,
            'verification_code' => '123456',
        ]);
        Setting::factory()->create(['code' => 'name', 'value' => 'Test App']);
        Route::shouldReceive('route')->with('emails.verify.code', [
            'emailaddress' => 1,
            'code' => '123456',
        ])->andReturn('http://test.app/verify/1/123456');

        $mailable = new VerifyEmail($emailAddress);
        $content = $mailable->content();

        $this->assertEquals('emails.verifyemail', $content->markdown);
        $this->assertArrayHasKey('url', $content->with);
        $this->assertEquals('http://test.app/verify/1/123456', $content->with['url']);
        $this->assertEquals($emailAddress, $content->with['email']);
        $this->assertEquals('Test App', $content->with['name']);
    }
}
