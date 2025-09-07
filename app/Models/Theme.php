<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * App\Models\Theme
 *
 * @mixin IdeHelperTheme
 * @property int $id
 * @property string $name
 * @property string $code
 * @property int $readonly
 * @property int $active
 * @property int $dark_mode
 * @property string $primary
 * @property string $nav_background
 * @property string $seat_available
 * @property string $seat_disabled
 * @property string $seat_taken
 * @property string $seat_clan
 * @property string $seat_selected
 * @property string|null $css
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|Theme newModelQuery()
 * @method static Builder|Theme newQuery()
 * @method static Builder|Theme query()
 * @method static Builder|Theme whereActive($value)
 * @method static Builder|Theme whereCode($value)
 * @method static Builder|Theme whereCreatedAt($value)
 * @method static Builder|Theme whereCss($value)
 * @method static Builder|Theme whereDarkMode($value)
 * @method static Builder|Theme whereId($value)
 * @method static Builder|Theme whereName($value)
 * @method static Builder|Theme whereNavBackground($value)
 * @method static Builder|Theme wherePrimary($value)
 * @method static Builder|Theme whereReadonly($value)
 * @method static Builder|Theme whereSeatAvailable($value)
 * @method static Builder|Theme whereSeatClan($value)
 * @method static Builder|Theme whereSeatDisabled($value)
 * @method static Builder|Theme whereSeatSelected($value)
 * @method static Builder|Theme whereSeatTaken($value)
 * @method static Builder|Theme whereUpdatedAt($value)
 * @mixin Eloquent
 */
class Theme extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'readonly',
        'active',
        'dark_mode',
        'primary',
        'nav_background',
        'seat_available',
        'seat_disabled',
        'seat_taken',
        'seat_clan',
        'seat_selected',
        'css'
    ];

    public function rgb(string $property): string
    {
        $colour = $this->{$property};
        if (!preg_match('/^#(?:(?:[0-9a-f]{3}){1,2}|(?:[0-9a-f]{4}){1,2})$/i', $colour)) {
            return '0, 0, 0';
        }

        $hex = str_replace('#', '', $colour);
        if (strlen($hex) === 3) {
            $hex = str_repeat(substr($hex, 0, 1), 2) . str_repeat(substr($hex, 1, 1), 2) . str_repeat(substr($hex, 2, 1), 2);
        }
        $rgb = [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
        return implode(', ', $rgb);
    }
}
