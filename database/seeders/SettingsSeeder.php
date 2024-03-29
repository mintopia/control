<?php

namespace Database\Seeders;

use App\Enums\SettingType;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            'name' => (object)[
                'name' => 'Site Name',
                'default' => 'Control',
                'validation' => 'required|string|max:200|min:2',
            ],
            'terms' => (object)[
                'name' => 'Terms and Conditions URL',
                'validation' => 'sometimes|nullable|string|url:http,https',
            ],
            'privacypolicy' => (object)[
                'name' => 'Privacy Policy URL',
                'validation' => 'sometimes|nullable|string|url:http,https',
            ],
            'logo-light' => (object)[
                'name' => 'Site Logo (Light Version)',
                'validation' => 'sometimes|nullable|string|url:http,https',
                'description' => 'A light version of the logo for use on dark backgrounds',
            ],
            'logo-dark' => (object)[
                'name' => 'Site Logo (Dark Version)',
                'validation' => 'sometimes|nullable|string|url:http,https',
                'description' => 'A dark version of the logo for use on light backgrounds',
            ],
            'cover-image' => (object)[
                'name' => 'Login Cover Image',
                'validation' => 'sometimes|nullable|string|url:http,https',
                'description' => 'URL for the large image shown during login and signup',
            ],
            'favicon' => (object)[
                'name' => 'Favicon',
                'validation' => 'sometimes|nullable|string|url:http,https',
                'description' => 'URL for a favicon to use',
            ],
            'discord.server.name' => (object)[
                'name' => 'Discord Server Name',
                'hidden' => true,
            ],
            'discord.server.id' => (object)[
                'name' => 'Discord Server ID',
                'hidden' => true,
            ],
            'disable-ticket-transfers' => (object)[
                'name' => 'Disable Ticket Transfers',
                'description' => 'Whether or not ticket transfers should be disabled',
                'type' => SettingType::stBoolean,
                'default' => false,
            ],
        ];
        foreach ($settings as $code => $setting) {
            $this->updateSetting($code, $setting);
        }
    }

    protected function updateSetting(string $code, object $data): void
    {
        $setting = Setting::whereCode($code)->first();
        if (!$setting) {
            $setting = new Setting();
            $setting->code = $code;
            $setting->value = $data->default ?? null;
        }
        $setting->hidden = $data->hidden ?? false;
        $setting->name = $data->name;
        $setting->description = $data->description ?? null;
        $setting->encrypted = $data->encrypted ?? false;
        $setting->validation = $data->validation ?? '';
        $setting->type = $data->type ?? SettingType::stString;
        $setting->save();
    }
}
