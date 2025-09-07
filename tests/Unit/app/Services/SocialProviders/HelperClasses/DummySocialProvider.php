<?php

namespace Tests\Unit\app\Services\SocialProviders\HelperClasses;

use App\Models\LinkedAccount;
use App\Models\SocialProvider;
use App\Services\SocialProviders\AbstractSocialProvider;

class DummySocialProvider extends AbstractSocialProvider
{
    protected string $name = 'Dummy Social';
    protected string $code = 'dummy';
    protected string $socialiteProviderCode = 'dummy';

    public function __construct(?SocialProvider $provider = null, ?string $redirectUrl = null)
    {
        parent::__construct($provider, $redirectUrl);
    }

    // Provide a no-op updateAccount so tests exercising user() don't fail
    protected function updateAccount(LinkedAccount $account, $remoteUser): void
    {
        // intentionally empty for tests
    }
}
