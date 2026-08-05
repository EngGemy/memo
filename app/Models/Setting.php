<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    /** Brand defaults — mirrored in the CSS custom properties. */
    public const DEFAULTS = [
        'logo_path' => 'assets/memo-logo.png',
        'logo_nav' => 32,
        'logo_hero' => 76,
        'logo_foot' => 40,
        'watermark_path' => null,     // private disk, composed in the dashboard
        'watermark_phone' => '01095236175',
        'brand_name' => 'MEMO STORE',
    ];

    public static function brand(): array
    {
        return Cache::rememberForever('brand.v2', function () {
            if (! Schema::hasTable('settings')) {
                return self::DEFAULTS;
            }

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

        Cache::forget('brand.v2');
    }
}
