<?php

namespace Tests\Unit\app\Observers;

use App\Models\Theme;
use App\Observers\ThemeObserver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

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

    public function testSavedDeactivatesOtherActiveThemes()
    {
        // Create one theme that is active and another that is inactive to avoid the
        // observer deactivating the first during creation of the second.
        $other = Theme::factory()->create(['name' => 'Theme Other', 'active' => true]);
        $theme = Theme::factory()->create(['name' => 'Theme Current', 'active' => false]);

        // Sanity: other is active, theme is not (we'll simulate activating it)
        $this->assertEquals(1, $other->fresh()->active);
        $this->assertEquals(0, $theme->fresh()->active);

        // Simulate the theme having been saved as active (call saved observer)
        $theme->active = 1;
        $observer = new ThemeObserver();
        $observer->saved($theme);

        // After saved(), other active themes should be deactivated
        $this->assertEquals(0, $other->fresh()->active);
        // In-memory model remains active, but it wasn't persisted in this test
        $this->assertEquals(1, $theme->active);
    }
}
