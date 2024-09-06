<?php

namespace App\Models;

use App\Casts\SettingValue;
use App\Enums\SettingType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;

/**
 * App\Models\ProviderSetting
 *
 * @mixin IdeHelperProviderSetting
 * @property int $id
 * @property string $provider_type
 * @property int $provider_id
 * @property string $name
 * @property string $code
 * @property string|null $description
 * @property SettingType $type
 * @property int $encrypted
 * @property string|null $validation
 * @property mixed|null|null $value
 * @property int $order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Model|\Eloquent $provider
 * @method static Builder|ProviderSetting newModelQuery()
 * @method static Builder|ProviderSetting newQuery()
 * @method static Builder|ProviderSetting ordered(string $direction = 'asc')
 * @method static Builder|ProviderSetting query()
 * @method static Builder|ProviderSetting whereCode($value)
 * @method static Builder|ProviderSetting whereCreatedAt($value)
 * @method static Builder|ProviderSetting whereDescription($value)
 * @method static Builder|ProviderSetting whereEncrypted($value)
 * @method static Builder|ProviderSetting whereId($value)
 * @method static Builder|ProviderSetting whereName($value)
 * @method static Builder|ProviderSetting whereOrder($value)
 * @method static Builder|ProviderSetting whereProviderId($value)
 * @method static Builder|ProviderSetting whereProviderType($value)
 * @method static Builder|ProviderSetting whereType($value)
 * @method static Builder|ProviderSetting whereUpdatedAt($value)
 * @method static Builder|ProviderSetting whereValidation($value)
 * @method static Builder|ProviderSetting whereValue($value)
 * @mixin \Eloquent
 */
class ProviderSetting extends Model implements Sortable
{
    use HasFactory, SortableTrait;

    protected $casts = [
        'value' => SettingValue::class,
        'type' => SettingType::class,
    ];

    public function buildSortQuery(): Builder
    {
        return static::query()->where('provider_id', $this->provider_id)->where('provider_type', $this->provider_type);
    }

    public function provider(): MorphTo
    {
        return $this->morphTo();
    }

    public function isRequired(): bool
    {
        return str_contains($this->validation ?? '', 'required');
    }
}
