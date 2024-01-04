<?php

namespace App\Models;

use App\Models\Traits\ToString;
use App\Services\Contracts\SocialProviderContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperSocialProvider
 */
class SocialProvider extends Model
{
    use HasFactory, ToString;

    protected $hidden = [
        'client_secret',
        'token',
    ];

    protected $casts = [
        'client_secret' => 'encrypted',
        'token' => 'encrypted',
    ];

    public function accounts(): HasMany
    {
        return $this->hasMany(LinkedAccount::class);
    }

    public function redirect(?string $redirectUrl = null)
    {
        return $this->getProvider($redirectUrl)->redirect();
    }

    public function getProvider(?string $redirectUrl = null): SocialProviderContract
    {
        return new $this->provider_class($this, $redirectUrl);
    }

    public function user(?string $redirectUrl = null)
    {
        return $this->getProvider($redirectUrl)->user();
    }

    public function configMapping(): array
    {
        return $this->getProvider()->configMapping();
    }

    protected function toStringName(): string
    {
        return $this->code;
    }
}
