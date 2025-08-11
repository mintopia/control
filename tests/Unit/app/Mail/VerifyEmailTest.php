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
        $emailAddress = EmailAddress::factory()->make(['user_id' => null]);
        $mailable = new VerifyEmail($emailAddress);

        $this->assertIsArray($mailable->attachments());
        $this->assertEmpty($mailable->attachments());
    }

    //CHECK Needs more work - previous efforts failed due to "class already exists"

}
