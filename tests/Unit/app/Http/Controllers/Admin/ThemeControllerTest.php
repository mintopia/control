<?php

namespace Tests\Unit\app\Http\Controllers\Admin;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Controllers\Admin\ThemeController;
use App\Models\Theme;

class ThemeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testCreateEditDelete()
    {
        $theme = Theme::factory()->create(['readonly' => false]);
        $c = new ThemeController();
        $this->assertTrue(is_object($c->create()));
        $this->assertTrue(is_object($c->edit($theme)));
        $this->assertTrue(is_object($c->delete($theme)));
    }
}
