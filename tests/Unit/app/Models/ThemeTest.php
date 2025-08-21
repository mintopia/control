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

    public function testRgbReturnsCorrectValueFor6DigitHex()
    {
        $theme = new Theme();
        $theme->primary = '#ff8800';
        $this->assertEquals('255, 136, 0', $theme->rgb('primary'));
    }

    public function testRgbReturnsCorrectValueFor3DigitHex()
    {
        $theme = new Theme();
        $theme->primary = '#f80';
        $this->assertEquals('255, 136, 0', $theme->rgb('primary'));
    }

    public function testRgbReturnsBlackForInvalidHex()
    {
        $theme = new Theme();
        $theme->primary = 'not-a-color';
        $this->assertEquals('0, 0, 0', $theme->rgb('primary'));
    }

    public function testRgbReturnsBlackForMissingHash()
    {
        $theme = new Theme();
        $theme->primary = 'ff8800';
        $this->assertEquals('0, 0, 0', $theme->rgb('primary'));
    }
}
