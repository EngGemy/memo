<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Logo, sizing, and the burned watermark.
 *
 * The watermark PNG arrives already composed - the dashboard draws the logo
 * and phone number on a canvas and uploads the result. That keeps what you
 * preview and what ffmpeg burns in byte-identical, and avoids needing a font
 * file installed on the server.
 *
 * It goes to the private disk: a public watermark file is a template for
 * anyone wanting to fake your branding.
 */
class BrandController extends Controller
{
    private const BOUNDS = [
        'logo_nav'  => [18, 64],
        'logo_hero' => [40, 180],
        'logo_foot' => [18, 90],
    ];

    public function show(): JsonResponse
    {
        $brand = Setting::brand();
        $brand['watermark_ready'] = ! empty($brand['watermark_path']);
        unset($brand['watermark_path']);   // never expose the private path

        return response()->json($brand);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'logo'            => ['nullable','image','mimes:png,svg,webp','max:2048'],
            'watermark'       => ['nullable','image','mimes:png','max:2048'],
            'watermark_phone' => ['nullable','string','max:40'],
            'brand_name'      => ['nullable','string','max:80'],
            'logo_nav'        => ['required','integer','between:18,64'],
            'logo_hero'       => ['required','integer','between:40,180'],
            'logo_foot'       => ['required','integer','between:18,90'],
        ]);

        $pairs = [];

        foreach (self::BOUNDS as $key => [$min, $max]) {
            $pairs[$key] = max($min, min($max, (int) $data[$key]));
        }

        foreach (['watermark_phone','brand_name'] as $k) {
            if (! empty($data[$k])) {
                $pairs[$k] = $data[$k];
            }
        }

        if ($request->hasFile('logo')) {
            $name = 'memo-logo-'.now()->timestamp.'.'.$request->file('logo')->extension();
            $request->file('logo')->storePubliclyAs('brand', $name, 'public');
            $pairs['logo_path'] = 'storage/brand/'.$name;
        }

        if ($request->hasFile('watermark')) {
            Storage::disk('private')->putFileAs('brand', $request->file('watermark'), 'watermark.png');
            $pairs['watermark_path'] = 'brand/watermark.png';
        }

        Setting::put($pairs);

        return response()->json(['message' => 'Branding saved.', 'brand' => $this->show()->getData(true)]);
    }
}
