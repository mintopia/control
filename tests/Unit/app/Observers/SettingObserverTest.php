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

    public function testCreatedHandlerIsNoop()
    {
        $setting = new Setting();
        $observer = new SettingObserver();
        $observer->created($setting);
        $this->assertTrue(true);
    }

    public function testUpdatedHandlerIsNoop()
    {
        $setting = new Setting();
        $observer = new SettingObserver();
        $observer->updated($setting);
        $this->assertTrue(true);
    }

    public function testDeletedHandlerIsNoop()
    {
        $setting = new Setting();
        $observer = new SettingObserver();
        $observer->deleted($setting);
        $this->assertTrue(true);
    }

    public function testRestoredHandlerIsNoop()
    {
        $setting = new Setting();
        $observer = new SettingObserver();
        $observer->restored($setting);
        $this->assertTrue(true);
    }

    public function testForceDeletedHandlerIsNoop()
    {
        $setting = new Setting();
        $observer = new SettingObserver();
        $observer->forceDeleted($setting);
        $this->assertTrue(true);
    }
}
