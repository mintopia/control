<?php

namespace Tests\Unit\app\Observers;

use Tests\TestCase;
use App\Observers\SettingObserver;
use App\Models\Setting;

class SettingObserverTest extends TestCase
{
    public function testSavedClearsCache()
    {
        $setting = $this->getMockBuilder(Setting::class)->onlyMethods(['clearCache'])->getMock();
        $setting->expects($this->once())->method('clearCache');
        $observer = new SettingObserver();
        $observer->saved($setting);
    }

    public function testOtherHandlersAreSkipped()
    {
        $this->markTestSkipped('SettingObserver other handlers not implemented yet');
    }
}
