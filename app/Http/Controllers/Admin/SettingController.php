<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SettingType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SettingUpdateRequest;
use App\Models\Setting;
use App\Models\SocialProvider;
use App\Models\Theme;
use App\Models\TicketProvider;

class SettingController extends Controller
{
    public function index()
    {
        $socialProviders = SocialProvider::get();
        $ticketProviders = TicketProvider::get();
        $settings = Setting::whereHidden(false)->ordered()->get();
        $themes = Theme::get();
        return view('admin.settings.index', [
            'socialProviders' => $socialProviders,
            'ticketProviders' => $ticketProviders,
            'themes' => $themes,
            'settings' => $settings,
            'discordId' => Setting::whereCode('discord.server.id')->first(),
            'discordName' => Setting::whereCode('discord.server.name')->first(),
            'discordProvider' => SocialProvider::whereCode('discord')->first(),
        ]);
    }

    public function update(SettingUpdateRequest $request)
    {
        $settings = Setting::whereHidden(false)->get();
        foreach ($settings as $setting) {
            if ($request->has($setting->code)) {
                $value = $request->input($setting->code);
                if ($setting->type === SettingType::stBoolean) {
                    $value = (bool)$value;
                }
                $setting->value = $value;
                $setting->save();
            }
        }
        return response()->redirectToRoute('admin.settings.index')->with('successMessage', 'Settings have been updated');
    }

    public function add_discord()
    {
        $provider = SocialProvider::whereCode('discord')->first()->getProvider(route('admin.settings.discord_return'));
        return $provider->addBotToServer();
    }

    public function add_discord_return()
    {
        $serverName = Setting::whereCode('discord.server.name')->first();
        $serverId = Setting::whereCode('discord.server.id')->first();
        try {
            $response = SocialProvider::whereCode('discord')->first()->getProvider(route('admin.settings.discord_return'))->bot();
            $serverName->value = $response->accessTokenResponseBody['guild']['name'];
            $serverId->value = $response->accessTokenResponseBody['guild']['id'];
            return response()->redirectToRoute('admin.settings.index')->with('successMessage', "Link to {{ $serverName->value }} has been successful");
        } catch (\Exception $ex) {
            $serverName->value = null;
            $serverId->value = null;
            return response()->redirectToRoute('admin.settings.index')->with('errorMessage', "Unable to link to Discord server");
        } finally {
            $serverName->save();
            $serverId->save();
        }
    }
}
