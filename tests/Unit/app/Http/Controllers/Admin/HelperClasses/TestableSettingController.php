<?php

namespace Tests\Unit\app\Http\Controllers\Admin\HelperClasses;

use App\Http\Controllers\Admin\SettingController;

class TestableSettingController extends SettingController
{
    public function callGetDiscordProvider(?string $redirectUrl = null)
    {
        return $this->getDiscordProvider($redirectUrl);
    }
}
