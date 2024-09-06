<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Theme newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Theme newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Theme query()
 * @method static \Illuminate\Database\Eloquent\Builder|Theme whereActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Theme whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Theme whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Theme whereCss($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Theme whereDarkMode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Theme whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Theme whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Theme whereNavBackground($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Theme wherePrimary($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Theme whereReadonly($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Theme whereSeatAvailable($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Theme whereSeatClan($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Theme whereSeatDisabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Theme whereSeatSelected($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Theme whereSeatTaken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Theme whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Theme extends Model
{
    use HasFactory;

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
