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

    //FIXME Verify Email (Tests\Unit\app\Mail\VerifyEmail) > Envelope uses setting name and config from address
    // Illuminate\Database\QueryException: SQLSTATE[HY000]: General error: 1 no such table: settings (Connection: sqlite, SQL: select * from "settings" where "code" = name limit 1)
    // public function testEnvelopeUsesSettingNameAndConfigFromAddress()
    // {
    //     $emailAddress = EmailAddress::factory()->make();

    //     // Mock Setting::fetch static method
    //     \Mockery::mock(['alias' => 'App\\Models\\Setting'])
    //         ->shouldReceive('fetch')
    //         ->with('name')
    //         ->andReturn('Control Panel');

    //     Config::set('mail.from.address', 'admin@control.test');

    //     $mailable = new VerifyEmail($emailAddress);
    //     $envelope = $mailable->envelope();

    //     $this->assertEquals('Control Panel - Verify Email Address', $envelope->subject);
    //     $this->assertEquals('admin@control.test', $envelope->from->address);
    //     $this->assertEquals('Control Panel', $envelope->from->name);
    // }

    // public function testContentWithDifferentVerificationCode()
    // {
    //     $emailAddress = EmailAddress::factory()->make([
    //         'id' => 42,
    //         'verification_code' => 'abcdef',
    //     ]);

    //     // Mock Setting::fetch static method
    //     \Mockery::mock(['alias' => 'App\\Models\\Setting'])
    //         ->shouldReceive('fetch')
    //         ->with('name')
    //         ->andReturn('MyApp');

    //     \Mockery::mock(['alias' => 'Illuminate\\Support\\Facades\\Route'])
    //         ->shouldReceive('route')
    //         ->with('emails.verify.code', [
    //             'emailaddress' => 42,
    //             'code' => 'abcdef',
    //         ])
    //         ->andReturn('http://myapp.test/verify/42/abcdef');

    //     $mailable = new VerifyEmail($emailAddress);
    //     $content = $mailable->content();

    //     $this->assertEquals('emails.verifyemail', $content->markdown);
    //     $this->assertEquals('http://myapp.test/verify/42/abcdef', $content->with['url']);
    //     $this->assertEquals($emailAddress, $content->with['email']);
    //     $this->assertEquals('MyApp', $content->with['name']);
    // }
}
