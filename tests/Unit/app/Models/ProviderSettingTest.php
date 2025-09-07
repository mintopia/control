<?php

namespace Tests\Unit\app\Models;

use App\Models\ProviderSetting;
use App\Models\SocialProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProviderSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_build_sort_query_scopes_to_provider()
    {
        $prov = SocialProvider::factory()->create(['code' => 'psprov']);
        $ps = ProviderSetting::factory()->create(['provider_id' => $prov->id, 'provider_type' => get_class($prov), 'code' => 'x']);

        $query = $ps->buildSortQuery();
        $this->assertStringContainsString('where', $query->toSql());
    }

    public function test_provider_relation_returns_provider()
    {
        $prov = SocialProvider::factory()->create(['code' => 'psprov2']);
        $ps = ProviderSetting::factory()->create(['provider_id' => $prov->id, 'provider_type' => get_class($prov), 'code' => 'y']);

        $this->assertEquals($prov->id, $ps->provider->id);
    }

    public function test_is_required_detects_required_in_validation()
    {
        $ps = new ProviderSetting();
        $ps->validation = 'required|string';
        $this->assertTrue($ps->isRequired());

        $ps2 = new ProviderSetting();
        $ps2->validation = 'nullable|string';
        $this->assertFalse($ps2->isRequired());
    }
}
