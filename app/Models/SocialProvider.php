<?php

namespace App\Models;

use App\Models\Traits\ToString;
use App\Services\Contracts\SocialProviderContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * App\Models\SocialProvider
 *
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string $provider_class
 * @property int $supports_auth
 * @property int $enabled
 * @property int $auth_enabled
 * @property string|null $client_id
 * @property mixed|null $client_secret
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LinkedAccount> $accounts
 * @property-read int|null $accounts_count
 * @method static \Illuminate\Database\Eloquent\Builder|SocialProvider newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SocialProvider newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SocialProvider query()
 * @method static \Illuminate\Database\Eloquent\Builder|SocialProvider whereAuthEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SocialProvider whereClientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SocialProvider whereClientSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SocialProvider whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SocialProvider whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SocialProvider whereEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SocialProvider whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SocialProvider whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SocialProvider whereProviderClass($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SocialProvider whereSupportsAuth($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SocialProvider whereUpdatedAt($value)
 * @mixin \Eloquent
 * @mixin IdeHelperSocialProvider
 */
class SocialProvider extends Model
{
    use HasFactory, ToString;

    protected $hidden = [
        'client_secret',
    ];

    protected $casts = [
        'client_secret' => 'encrypted',
    ];
    protected function toStringName(): string
    {
        return $this->code;
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(LinkedAccount::class);
    }

    public function getProvider(?string $redirectUrl = null): SocialProviderContract
    {
        return new $this->provider_class($this, $redirectUrl);
    }

    public function redirect(?string $redirectUrl = null)
    {
        return $this->getProvider($redirectUrl)->redirect();
    }

    public function user(?string $redirectUrl = null)
    {
        return $this->getProvider($redirectUrl)->user();
    }

    public function configMapping(): array
    {
        return $this->getProvider()->configMapping();
    }
}
