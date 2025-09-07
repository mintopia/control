<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SettingType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SettingUpdateRequest;
use App\Models\Setting;
use App\Models\SocialProvider;
use App\Models\Theme;
use App\Models\TicketProvider;
use Exception;

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
            if ($request->has($setting->code) || $setting->type === SettingType::stBoolean) {
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

    public function addDiscord()
    {
        $provider = $this->getDiscordProvider();
        return $provider->addBotToServer();
    }

    /**
     * Return an instance of the configured Discord provider.
     * Extracted so tests can override or call provider retrieval directly.
     */
    protected function getDiscordProvider(?string $redirectUrl = null)
    {
        $redirect = $redirectUrl ?? route('admin.settings.discord_return');
        return SocialProvider::whereCode('discord')->first()->getProvider($redirect);
    }

    public function addDiscordReturn()
    {
        $serverName = Setting::whereCode('discord.server.name')->first();
        $serverId = Setting::whereCode('discord.server.id')->first();
        try {
            $response = $this->callDiscordBot();
            $serverName->value = $response->accessTokenResponseBody['guild']['name'];
            $serverId->value = $response->accessTokenResponseBody['guild']['id'];
            return response()->redirectToRoute('admin.settings.index')->with('successMessage', "Link to {{ $serverName->value }} has been successful");
        } catch (Exception $ex) {
            $serverName->value = null;
            $serverId->value = null;
            return response()->redirectToRoute('admin.settings.index')->with('errorMessage', "Unable to link to Discord server");
        } finally {
            $serverName->save();
            $serverId->save();
        }
    }

    /**
     * Call the provider bot() flow and return the response object.
     * Extracted so tests can stub or call this method directly.
     */
    protected function callDiscordBot(?string $redirectUrl = null)
    {
        $provider = $this->getDiscordProvider($redirectUrl);
        return $provider->bot();
    }
}
