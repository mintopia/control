<?php

namespace App\Services\SocialProviders;

use App\Models\LinkedAccount;
use Laravel\Socialite\Facades\Socialite;
use SocialiteProviders\Twitch\Provider;

class TwitchProvider extends AbstractSocialProvider
{
    protected string $name = 'Twitch';
    protected string $code = 'twitch';
    protected string $socialiteProviderCode = 'twitch';
    protected bool $supportsAuth = true;

    protected function getSocialiteProvider()
    {
        return Socialite::buildProvider(Provider::class, [
            'client_id' => $this->provider->client_id,
            'client_secret' => $this->provider->client_secret,
            'redirect' => $this->redirectUrl,
        ]);
    }

    protected function updateAccount(LinkedAccount $account, $remoteUser): void
    {
        $account->avatar_url = $remoteUser->getAvatar();
        $account->refresh_token = $remoteUser->refreshToken;
        $account->access_token = $remoteUser->token;
        $account->name = $remoteUser->getNickname();
    }
}
