<?php

namespace Tests\Unit\app\Mail;

use App\Mail\VerifyEmail;
use App\Models\EmailAddress;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Mailables\Address;
use Tests\TestCase;

class VerifyEmailTest extends TestCase
{
    use RefreshDatabase;

    public function testAttachmentsReturnsEmptyArray()
    {
        $emailAddress = EmailAddress::factory()->create();
        $mailable = new VerifyEmail($emailAddress);

        $this->assertIsArray($mailable->attachments());
        $this->assertEmpty($mailable->attachments());
    }

    public function testEnvelopeHasCorrectSubjectAndFrom()
    {
        // Ensure the application name setting exists so the mailable composes the subject correctly
        Setting::factory()->create(["code" => 'name', 'value' => 'My App']);

        $emailAddress = EmailAddress::factory()->create(['verification_code' => 'ABC123', 'verification_sent_at' => now()]);
        $mailable = new VerifyEmail($emailAddress);

        $envelope = $mailable->envelope();

        $this->assertEquals('My App - Verify Email Address', $envelope->subject);
        $this->assertInstanceOf(Address::class, $envelope->from);
        $this->assertIsString($envelope->from->address);
        $this->assertNotEmpty($envelope->from->address);
    }

    public function testContentIncludesUrlEmailAndAppName()
    {
        Setting::factory()->create(["code" => 'name', 'value' => 'My App']);

        $emailAddress = EmailAddress::factory()->create(['verification_code' => 'ABC123', 'verification_sent_at' => now()]);
        $mailable = new VerifyEmail($emailAddress);

        $content = $mailable->content();

        $this->assertEquals('emails.verifyemail', $content->markdown);

        $expectedUrl = route('emails.verify.code', [
            'emailaddress' => $emailAddress->id,
            'code' => $emailAddress->verification_code,
        ]);

        $this->assertArrayHasKey('url', $content->with);
        $this->assertEquals($expectedUrl, $content->with['url']);
        $this->assertSame($emailAddress, $content->with['email']);
        $this->assertEquals('My App', $content->with['name']);
    }

    public function testRenderContainsUrlAndName()
    {
        Setting::factory()->create(["code" => 'name', 'value' => 'My App']);

        $emailAddress = EmailAddress::factory()->create(['verification_code' => 'ABC123', 'verification_sent_at' => now()]);
        $mailable = new VerifyEmail($emailAddress);

        $html = $mailable->render();

        $expectedUrl = route('emails.verify.code', [
            'emailaddress' => $emailAddress->id,
            'code' => $emailAddress->verification_code,
        ]);

        $this->assertStringContainsString($expectedUrl, $html);
        $this->assertStringContainsString('My App', $html);
        $this->assertStringContainsString($emailAddress->user->nickname, $html);
    }
}
