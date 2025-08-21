<?php

namespace Tests\Unit\app\Observers;

use Tests\TestCase;
use App\Observers\ThemeObserver;
use App\Models\Theme;

class ThemeObserverTest extends TestCase
{
    public function testSavingSetsCodeFromName()
    {
        $theme = new Theme(['name' => 'Test Theme']);
        $observer = new ThemeObserver();
        $observer->saving($theme);
        $this->assertNotEmpty($theme->code);
    }
}
