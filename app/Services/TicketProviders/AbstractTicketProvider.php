<?php

namespace App\Services\TicketProviders;

use App\Enums\SettingType;
use App\Models\EmailAddress;
use App\Models\ProviderSetting;
use App\Models\TicketProvider;
use App\Services\Contracts\TicketProviderContract;
use Illuminate\Console\OutputStyle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

abstract class AbstractTicketProvider implements TicketProviderContract
{
    protected string $name;
    protected string $code;
    protected ?TicketProvider $provider = null;

    public function __construct(?TicketProvider $provider = null)
    {
        if ($provider) {
            $this->setProvider($provider);
        }
    }

    protected function setProvider(TicketProvider $provider): void
    {
        $this->provider = $provider;
    }

    public function configMapping(): array
    {
        return [
            'apikey' => (object)[
                'name' => 'API Key',
                'validation' => 'required|string',
            ],
            'webhook_secret' => (object)[
                'name' => 'Webhook Secret',
                'validation' => 'required|string',
            ],
        ];
    }

    public function install(): TicketProvider
    {
        $provider = TicketProvider::whereCode($this->code)->first();
        $this->provider = $provider;
        if (!$provider) {
            $provider = new TicketProvider();
            $provider->name = $this->name;
            $provider->code = $this->code;
            $provider->provider_class = get_called_class();
            $provider->enabled = false;
            DB::transaction(function() use ($provider) {
                $provider->save();

                $this->installSettings();
            });
        }
        return $provider;
    }

    public function installSettings(): void
    {
        foreach ($this->configMapping() as $code => $config) {
            $setting = $this->provider->settings()->whereCode($code)->first();
            if (!$setting) {
                $setting = new ProviderSetting();
                $setting->provider()->associate($this->provider);
                $setting->code = $code;
                // Only set value initially
                $setting->value = $config->value ?? null;
            }
            $setting->name = $config->name;
            $setting->validation = $config->validation ?? null;
            $setting->encrypted = $config->encrypted ?? false;
            $setting->description = $config->description ?? null;
            $setting->type = $config->type ?? SettingType::stString;
            if ($setting->isDirty()) {
                $setting->save();
            }
        }
    }

    public function processWebhook(Request $request): bool
    {
        return true;
    }

    public function syncTickets(string|EmailAddress $email): void
    {
    }

    public function getEvents(): array
    {
        return [];
    }

    public function getTicketTypes(string $eventExternalId): array
    {
        return [];
    }

    public function syncAllTickets(?OutputStyle $output): void
    {
    }
}
