<?php

namespace Tests\Unit\app\Enums;

use Tests\TestCase;
use App\Enums\SettingType;

class SettingTypeTest extends TestCase
{
    public function testEnumCasesExist()
    {
        $cases = SettingType::cases();
        $this->assertContains('stString', array_column($cases, 'name'));
        $this->assertContains('stBoolean', array_column($cases, 'name'));
    }
}
