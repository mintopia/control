<?php

namespace Tests\Unit\app\Services\Contracts\HelperClasses;

use App\Models\SocialProvider;
use App\Models\User;
use App\Services\Contracts\SocialProviderContract;
use Illuminate\Http\RedirectResponse;

class DummySocialProvider implements SocialProviderContract
{
    public function __construct(?SocialProvider $provider = null, ?string $redirectUrl = null)
    {
    }

    public function configMapping(): array
    {
        return [
            'client_id' => [
                'name' => 'Client ID',
                'validation' => 'required|string',
                'value' => 'dummy-client-id',
            ],
        ];
    }

    public function install(): SocialProvider
    {
        return new SocialProvider(['name' => 'Dummy', 'code' => 'dummy']);
    }

    public function redirect(): RedirectResponse
    {
        return new RedirectResponse('/dummy-redirect');
    }

    public function user(?User $localUser = null)
    {
        return $localUser ?: new User(['name' => 'Dummy User']);
    }
}
