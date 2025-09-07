<?php

namespace Tests\Feature\app\Services\HelperClasses;

use App\Models\LinkedAccount;
use App\Services\SocialProviders\AbstractSocialProvider;

class ResolveDummyProvider extends AbstractSocialProvider
{
    protected string $name = 'Resolve Dummy';
    protected string $code = 'resolve_dummy_test';
    protected string $socialiteProviderCode = 'resolve_dummy_code';

    protected function updateAccount(LinkedAccount $account, $remoteUser): void
    {
        // no-op
    }
}
