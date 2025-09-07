<?php

namespace App\Models;

use App\Casts\SettingValue;
use App\Enums\SettingType;
use App\Models\Traits\ToString;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
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
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|Setting newModelQuery()
 * @method static Builder|Setting newQuery()
 * @method static Builder|Setting ordered(string $direction = 'asc')
 * @method static Builder|Setting query()
 * @method static Builder|Setting whereCode($value)
 * @method static Builder|Setting whereCreatedAt($value)
 * @method static Builder|Setting whereDescription($value)
 * @method static Builder|Setting whereEncrypted($value)
 * @method static Builder|Setting whereHidden($value)
 * @method static Builder|Setting whereId($value)
 * @method static Builder|Setting whereName($value)
 * @method static Builder|Setting whereOrder($value)
 * @method static Builder|Setting whereType($value)
 * @method static Builder|Setting whereUpdatedAt($value)
 * @method static Builder|Setting whereValidation($value)
 * @method static Builder|Setting whereValue($value)
 * @mixin Eloquent
 */
class Setting extends Model implements Sortable
{
    use HasFactory;
    use SortableTrait;
    use ToString;

    protected static array $cached = [];

    protected $fillable = [
        'code',
        'name',
        'description',
        'encrypted',
        'hidden',
        'value',
        'validation',
        'type',
        'order'
    ];

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

    public function getValue()
    {
        return (object)[
            'code' => $this->code,
            'encrypted' => $this->encrypted,
            'value' => $this->encrypted ? Crypt::encrypt($this->value) : $this->value,
        ];
    }

    public function clearCache(): void
    {
        Log::debug("Clearing settings.{$this->code} from cache");
        unset(static::$cached[$this->code]);
        Cache::forget("settings.{$this->code}");
    }

    protected function toStringName(): string
    {
        return $this->code;
    }
}
