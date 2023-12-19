<?php

namespace App\Services\SocialProviders;

use App\Models\LinkedAccount;
use Laravel\Socialite\Facades\Socialite;
use SocialiteProviders\Steam\Provider;

class SteamProvider extends AbstractSocialProvider
{
    protected string $name = 'Steam';
    protected string $code = 'steam';
    protected string $socialiteProviderCode = 'steam';
    protected bool $supportsAuth = true;

    public function configMapping(): array
    {
        return [
            'client_secret' => (object)[
                'name' => 'API Key',
                'validation' => 'required|string',
            ],
        ];
    }

    protected function getSocialiteProvider()
    {
        $host = request()->getHost();
        return Socialite::buildProvider(Provider::class, [
            'client_id' => null,
            'client_secret' => $this->provider->client_secret,
            'redirect' => $this->redirectUrl,
            'allowed_hosts' => [
                $host,
            ],
        ]);
    }

    protected function updateAccount(LinkedAccount $account, $remoteUser): void
    {
        $account->avatar_url = $remoteUser->getAvatar();
        $account->name = $remoteUser->getNickname();
    }
}
