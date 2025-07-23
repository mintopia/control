<?php

namespace Tests\App;

use App\Mail\VerifyEmail;
use App\Models\EmailAddress;
use App\Models\Setting;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Address;
use PHPUnit\Framework\TestCase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Mockery;

class VerifyEmailTest extends TestCase
{
    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_envelope_returns_correct_subject_and_from()
    {
        $email = Mockery::mock(EmailAddress::class);
        Setting::shouldReceive('fetch')->with('name')->andReturn('TestApp');
        Config::shouldReceive('get')->with('mail.from.address')->andReturn('test@example.com');

        $mailable = new VerifyEmail($email);
        $envelope = $mailable->envelope();

        $this->assertInstanceOf(Envelope::class, $envelope);
        $this->assertEquals('TestApp - Verify Email Address', $envelope->subject);
        $this->assertEquals(new Address('test@example.com', 'TestApp'), $envelope->from);
    }

    public function test_content_returns_correct_markdown_and_data()
    {
        $email = Mockery::mock(EmailAddress::class);
        $email->id = 123;
        $email->verification_code = 'abc123';
        Setting::shouldReceive('fetch')->with('name')->andReturn('TestApp');
        Route::shouldReceive('has')->andReturn(true);
        Route::shouldReceive('route')->with('emails.verify.code', [
            'emailaddress' => 123,
            'code' => 'abc123'
        ])->andReturn('http://test/verify?emailaddress=123&code=abc123');

        $mailable = new VerifyEmail($email);
        $content = $mailable->content();

        $this->assertInstanceOf(Content::class, $content);
        $this->assertEquals('emails.verifyemail', $content->markdown);
        $this->assertEquals([
            'url' => 'http://test/verify?emailaddress=123&code=abc123',
            'email' => $email,
            'name' => 'TestApp',
        ], $content->with);
    }

    public function test_attachments_returns_empty_array()
    {
        $email = Mockery::mock(EmailAddress::class);
        $mailable = new VerifyEmail($email);
        $attachments = $mailable->attachments();
        $this->assertIsArray($attachments);
        $this->assertEmpty($attachments);
    }
}
