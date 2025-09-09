<?php

namespace Tests\Unit\app\Models;

use App\Models\ProviderSetting;
use App\Models\SocialProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProviderSettingTest extends TestCase
{
    use RefreshDatabase;

    public function testBuildSortQueryScopesToProvider()
    {
        $prov = SocialProvider::factory()->create(['code' => 'psprov']);
        $ps = ProviderSetting::factory()->create(['provider_id' => $prov->id, 'provider_type' => get_class($prov), 'code' => 'x']);

        $query = $ps->buildSortQuery();
        $this->assertStringContainsString('where', $query->toSql());
    }

    public function testProviderRelationReturnsProvider()
    {
        $prov = SocialProvider::factory()->create(['code' => 'psprov2']);
        $ps = ProviderSetting::factory()->create(['provider_id' => $prov->id, 'provider_type' => get_class($prov), 'code' => 'y']);

        $this->assertEquals($prov->id, $ps->provider->id);
    }

    public function testIsRequiredDetectsRequiredInValidation()
    {
        $ps = new ProviderSetting();
        $ps->validation = 'required|string';
        $this->assertTrue($ps->isRequired());

        $ps2 = new ProviderSetting();
        $ps2->validation = 'nullable|string';
        $this->assertFalse($ps2->isRequired());
    }
}
