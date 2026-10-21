<?php

namespace Tests\Unit\app\Http\Controllers\Admin\HelperClasses;

use App\Models\SocialProvider as SocialProviderModel;
use App\Models\User;
use App\Services\Contracts\SocialProviderContract;
use Illuminate\Http\RedirectResponse;

class ThrowingDiscordProvider implements SocialProviderContract
{
    public $provider;
    public $redirectUrl;

    public function __construct($provider = null, $redirectUrl = null)
    {
        $this->provider = $provider;
        $this->redirectUrl = $redirectUrl;
    }

    public function configMapping(): array
    {
        return [];
    }

    public function install(): SocialProviderModel
    {
        return $this->provider;
    }

    public function redirect(): RedirectResponse
    {
        return redirect()->to('/');
    }

    public function user(?User $localUser = null)
    {
        return null;
    }

    public function bot()
    {
        throw new \Exception('fail');
    }
}
