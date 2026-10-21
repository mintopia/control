<?php

namespace Tests\Unit\app\Enums;

use App\Enums\SettingType;
use Tests\TestCase;

class SettingTypeTest extends TestCase
{
    public function testEnumCasesExist()
    {
        $cases = SettingType::cases();
        $this->assertContains('stString', array_column($cases, 'name'));
        $this->assertContains('stBoolean', array_column($cases, 'name'));
    }
}
