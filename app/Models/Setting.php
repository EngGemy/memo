<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['key','value'];

    /** Brand defaults — mirrored in the CSS custom properties. */
    public const DEFAULTS = [
        'logo_path' => 'assets/memo-logo.png',
        'logo_nav'  => 32,
        'logo_hero' => 76,
        'logo_foot' => 40,
    ];

    public static function brand(): array
    {
        return Cache::rememberForever('brand', function () {
            return array_merge(self::DEFAULTS, static::query()
                ->whereIn('key', array_keys(self::DEFAULTS))
                ->pluck('value', 'key')
                ->toArray());
        });
    }

    public static function put(array $pairs): void
    {
        foreach ($pairs as $k => $v) {
            static::updateOrCreate(['key' => $k], ['value' => $v]);
        }

        Cache::forget('brand');
    }
}
