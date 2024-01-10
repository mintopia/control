<?php

namespace App\Services\SocialProviders;

use App\Models\LinkedAccount;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;
use SocialiteProviders\Discord\Provider;

class DiscordProvider extends AbstractSocialProvider
{
    protected string $name = 'Discord';
    protected string $code = 'discord';
    protected string $socialiteProviderCode = 'discord';
    protected bool $supportsAuth = true;

    protected function getSocialiteProvider()
    {
        return Socialite::buildProvider(Provider::class, [
            'client_id' => $this->provider->getSetting('client_id'),
            'client_secret' => $this->provider->getSetting('client_secret'),
            'redirect' => $this->redirectUrl,
        ]);
    }


    public function configMapping(): array
    {
        return array_merge(
            parent::configMapping(),
            [
                'token' => (object)[
                    'name' => 'Bot Token',
                    'validation' => 'sometimes|string|nullable',
                    'encrypted' => true,
                ],
            ],
        );
    }

    protected function updateAccount(LinkedAccount $account, $remoteUser): void
    {
        $account->avatar_url = $remoteUser->getAvatar();
        $account->refresh_token = $remoteUser->refreshToken;
        $account->access_token = $remoteUser->token;
        $account->name = $remoteUser->getNickname();
    }

    protected function getBotProvider()
    {
        return $this->getSocialiteProvider()->scopes(['email', 'identify', 'bot'])->with([
            'permissions' => '268435456',
        ]);
    }

    public function addBotToServer(): RedirectResponse
    {
        return $this->getBotProvider()->redirect();
    }

    public function bot()
    {
        return $this->getBotProvider()->user();
    }
}
