<?php

namespace Tests\Unit\app\Mail;

use Tests\TestCase;

use App\Mail\VerifyEmail;
use App\Models\EmailAddress;
use App\Models\Setting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;

class VerifyEmailTest extends TestCase
{
    public function testAttachmentsReturnsEmptyArray()
    {
        $emailAddress = EmailAddress::factory()->make();
        $mailable = new VerifyEmail($emailAddress);

        $this->assertIsArray($mailable->attachments());
        $this->assertEmpty($mailable->attachments());
    }

    public function testEnvelopeUsesSettingNameAndConfigFromAddress()
    {
        $emailAddress = EmailAddress::factory()->make();
        Setting::factory()->create(['code' => 'name', 'value' => 'Control Panel']);
        Config::set('mail.from.address', 'admin@control.test');

        $mailable = new VerifyEmail($emailAddress);
        $envelope = $mailable->envelope();

        $this->assertEquals('Control Panel - Verify Email Address', $envelope->subject);
        $this->assertEquals('admin@control.test', $envelope->from->address);
        $this->assertEquals('Control Panel', $envelope->from->name);
    }

    public function testContentWithDifferentVerificationCode()
    {
        $emailAddress = EmailAddress::factory()->make([
            'id' => 42,
            'verification_code' => 'abcdef',
        ]);
        Setting::factory()->create(['code' => 'name', 'value' => 'MyApp']);
        Route::shouldReceive('route')->with('emails.verify.code', [
            'emailaddress' => 42,
            'code' => 'abcdef',
        ])->andReturn('http://myapp.test/verify/42/abcdef');

        $mailable = new VerifyEmail($emailAddress);
        $content = $mailable->content();

        $this->assertEquals('emails.verifyemail', $content->markdown);
        $this->assertEquals('http://myapp.test/verify/42/abcdef', $content->with['url']);
        $this->assertEquals($emailAddress, $content->with['email']);
        $this->assertEquals('MyApp', $content->with['name']);
    }
}
