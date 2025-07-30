<?php

namespace Tests\Unit\app\Models;

use Tests\TestCase;
use App\Models\Theme;

class ThemeTest extends TestCase
{
    public function testCanInstantiateTheme()
    {
        $theme = new Theme();
        $this->assertInstanceOf(Theme::class, $theme);
    }
}
