<?php

use App\Enums\SettingType;
use App\Models\ProviderSetting;
use App\Models\SocialProvider;
use App\Models\TicketProvider;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $ticketProviders = TicketProvider::all();
        foreach ($ticketProviders as $provider) {
            $this->updateSettings($provider);
        }
        $socialProviders = SocialProvider::all();
        foreach ($socialProviders as $provider) {
            $this->updateSettings($provider);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        ProviderSetting::query()->delete();
    }

    protected function updateSettings(SocialProvider|TicketProvider $provider): void
    {
        $prov = $provider->getProvider();
        $configMap = $prov->configMapping();
        foreach ($configMap as $code => $config) {
            $this->updateSetting($code, $config, $provider);
        }
    }

    protected function updateSetting(string $code, object $config, SocialProvider|TicketProvider $provider): void
    {
        $encrypted = [
            'client_secret',
            'apikey',
            'webhook_secret',
            'token',
        ];

        $setting = $provider->settings()->whereCode($code)->first();
        if (!$setting) {
            $setting = new ProviderSetting();
            $setting->provider()->associate($provider);
            $setting->code = $code;
        }
        $setting->type = SettingType::stString;
        $setting->name = $config->name;
        $setting->description = $config->description ?? null;
        $setting->validation = $config->validation ?? null;
        $setting->encrypted = in_array($code, $encrypted);

        $value = $provider->{$code};
        if ($value && $setting->encrypted) {
            $value = Crypt::decryptString($value);
        }
        $setting->value = $value;

        $setting->save();
    }
};
