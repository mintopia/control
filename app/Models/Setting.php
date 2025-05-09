<?php

namespace App\Models;

use App\Casts\SettingValue;
use App\Enums\SettingType;
use App\Models\Traits\ToString;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;

/**
 * App\Models\Setting
 *
 * @mixin IdeHelperSetting
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property int $encrypted
 * @property int $hidden
 * @property mixed|null|null $value
 * @property string|null $validation
 * @property SettingType $type
 * @property int $order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Setting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Setting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Setting ordered(string $direction = 'asc')
 * @method static \Illuminate\Database\Eloquent\Builder|Setting query()
 * @method static \Illuminate\Database\Eloquent\Builder|Setting whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setting whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setting whereEncrypted($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setting whereHidden($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setting whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setting whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setting whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setting whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setting whereValidation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setting whereValue($value)
 * @mixin \Eloquent
 */
class Setting extends Model implements Sortable
{
    use HasFactory;
    use SortableTrait;
    use ToString;

    protected static array $cached = [];

    protected $casts = [
        'value' => SettingValue::class,
        'type' => SettingType::class,
    ];

    public static function fetch(string $code, $default = null)
    {
        if (isset(static::$cached[$code])) {
            Log::debug("Fetched settings.{$code} from setting cache");
            return static::$cached[$code];
        }
        $key = "settings.{$code}";
        if ($setting = Cache::get($key)) {
            Log::debug("Fetched {$key} from application cache");
            if ($setting->value === null) {
                return $default;
            }
            if ($setting->encrypted) {
                static::$cached[$code] = $setting->value;
                return Crypt::decrypt($setting->value);
            }
            static::$cached[$code] = $setting->value;
            return $setting->value;
        }
        $setting = Setting::whereCode($code)->first();
        Log::debug("Fetching {$key} from database");
        if ($setting === null) {
            Cache::put($key, $setting);
            static::$cached[$code] = null;
            return $default;
        }
        Cache::put($key, $setting->getValue());
        static::$cached[$code] = $setting->value;
        return $setting->value ?? $default;
    }

    public function clearCache(): void
    {
        Log::debug("Clearing settings.{$this->code} from cache");
        unset(static::$cached[$this->code]);
        Cache::forget("settings.{$this->code}");
    }

    public function getValue()
    {
        return (object)[
            'code' => $this->code,
            'encrypted' => $this->encrypted,
            'value' => $this->encrypted ? Crypt::encrypt($this->value) : $this->value,
        ];
    }

    protected function toStringName(): string
    {
        return $this->code;
    }
}
