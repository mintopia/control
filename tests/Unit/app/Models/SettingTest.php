<?php

namespace Tests\Unit\app\Models;

use Tests\TestCase;
use App\Models\Setting;

class SettingTest extends TestCase
{
    public function testCanInstantiateSetting()
    {
        $setting = new Setting();
        $this->assertInstanceOf(Setting::class, $setting);
    }
}
