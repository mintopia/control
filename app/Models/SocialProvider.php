<?php

namespace App\Models;

use App\Models\Traits\ToString;
use App\Services\Contracts\SocialProviderContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * App\Models\SocialProvider
 *
 * @mixin IdeHelperSocialProvider
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string $provider_class
 * @property int $supports_auth
 * @property int $enabled
 * @property int $auth_enabled
 * @property int $can_be_renamed
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LinkedAccount> $accounts
 * @property-read int|null $accounts_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProviderSetting> $settings
 * @property-read int|null $settings_count
 * @method static \Illuminate\Database\Eloquent\Builder|SocialProvider newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SocialProvider newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SocialProvider query()
 * @method static \Illuminate\Database\Eloquent\Builder|SocialProvider whereAuthEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SocialProvider whereCanBeRenamed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SocialProvider whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SocialProvider whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SocialProvider whereEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SocialProvider whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SocialProvider whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SocialProvider whereProviderClass($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SocialProvider whereSupportsAuth($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SocialProvider whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class SocialProvider extends Model
{
    use HasFactory;
    use ToString;

    protected array $_settings = [];

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

    public function settings(): MorphMany
    {
        return $this->morphMany(ProviderSetting::class, 'provider');
    }

    public function getSetting(string $code): mixed
    {
        if (isset($this->_settings[$code])) {
            return $this->_settings[$code];
        }
        $setting = $this->settings()->whereCode($code)->first();
        if (!$setting) {
            $this->_settings[$code] = null;
            return null;
        }
        $this->_settings[$code] = $setting->value;
        return $setting->value;
    }
}
