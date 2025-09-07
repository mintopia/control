<?php

namespace App\Models;

use App\Models\Traits\ToString;
use App\Services\Contracts\SocialProviderContract;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

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
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, LinkedAccount> $accounts
 * @property-read int|null $accounts_count
 * @property-read Collection<int, ProviderSetting> $settings
 * @property-read int|null $settings_count
 * @method static Builder|SocialProvider newModelQuery()
 * @method static Builder|SocialProvider newQuery()
 * @method static Builder|SocialProvider query()
 * @method static Builder|SocialProvider whereAuthEnabled($value)
 * @method static Builder|SocialProvider whereCanBeRenamed($value)
 * @method static Builder|SocialProvider whereCode($value)
 * @method static Builder|SocialProvider whereCreatedAt($value)
 * @method static Builder|SocialProvider whereEnabled($value)
 * @method static Builder|SocialProvider whereId($value)
 * @method static Builder|SocialProvider whereName($value)
 * @method static Builder|SocialProvider whereProviderClass($value)
 * @method static Builder|SocialProvider whereSupportsAuth($value)
 * @method static Builder|SocialProvider whereUpdatedAt($value)
 * @mixin Eloquent
 */
class SocialProvider extends Model
{
    use HasFactory;
    use ToString;

    protected $fillable = [
        'name',
        'code',
        'provider_class',
        'supports_auth',
        'enabled',
        'auth_enabled',
        'can_be_renamed',
    ];

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
        // Prefer resolving from the container if bound (tests may bind stubs)
        if (app()->bound($this->provider_class)) {
            return app()->make($this->provider_class, ['provider' => $this, 'redirectUrl' => $redirectUrl]);
        }
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

    public function settings(): MorphMany
    {
        return $this->morphMany(ProviderSetting::class, 'provider');
    }

    protected function toStringName(): string
    {
        return $this->code;
    }
}
