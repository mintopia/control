<?php

namespace Tests\Feature\app\Services\HelperClasses;

use App\Models\LinkedAccount;
use App\Services\SocialProviders\AbstractSocialProvider;

class InstallDummyProvider extends AbstractSocialProvider
{
    protected string $name = 'Install Dummy';
    // Use a fixed, simple code to avoid namespace/escape mismatches in tests
    protected string $code = 'install_dummy_test_fixed';
    protected string $socialiteProviderCode = 'install_dummy_code';

    // No-op
    protected function updateAccount(LinkedAccount $account, $remoteUser): void
    {
    }
}
