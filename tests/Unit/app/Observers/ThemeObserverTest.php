<?php

namespace Tests\Unit\app\Observers;

use Tests\TestCase;
use App\Observers\ThemeObserver;
use App\Models\Theme;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ThemeObserverTest extends TestCase
{
    use RefreshDatabase;
    public function testSavingSetsCodeFromName()
    {
        $theme = new Theme(['name' => 'Test Theme']);
        $observer = new ThemeObserver();
        $observer->saving($theme);
        $this->assertNotEmpty($theme->code);
    }

    public function testDeletedActivatesDefaultWhenNoActiveThemes()
    {
        // create a default theme code
        $default = Theme::factory()->create(['code' => 'default', 'active' => false]);
        $observer = new ThemeObserver();
        $observer->deleted($default);
        $this->assertEquals(1, $default->fresh()->active);
    }

    public function testOtherHandlersSkipped()
    {
        $this->markTestSkipped('ThemeObserver other handlers not implemented yet');
    }
}
