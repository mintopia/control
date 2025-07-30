<?php

namespace Tests\Unit\app\Models;

use Tests\TestCase;
use App\Models\SocialProvider;

class SocialProviderTest extends TestCase
{
    public function testCanInstantiateSocialProvider()
    {
        $provider = new SocialProvider();
        $this->assertInstanceOf(SocialProvider::class, $provider);
    }
}
