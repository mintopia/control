<?php

namespace Tests\Unit\app\Models\HelperClasses;

use App\Models\SocialProvider;
use App\Models\User;
use App\Services\Contracts\SocialProviderContract;
use Illuminate\Http\RedirectResponse;

class TestSocialProviderStub implements SocialProviderContract
{
    public function __construct(?SocialProvider $provider = null, ?string $redirectUrl = null)
    {
        // no-op
    }

    public function configMapping(): array
    {
        return [];
    }

    public function install(): SocialProvider
    {
        throw new Exception('Not implemented in test stub');
    }

    public function redirect(): RedirectResponse
    {
        return new RedirectResponse('/stub-redirect');
    }

    public function user(?User $localUser = null)
    {
        return null;
    }
}
