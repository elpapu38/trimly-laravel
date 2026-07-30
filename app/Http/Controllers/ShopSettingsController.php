<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\ShopHour;
use App\Models\ShopPhoto;
use App\Services\ImageUploader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ShopSettingsController extends Controller
{
    private function getOwnerShop(Request $request): Shop
    {
        $shop = Shop::where('owner_id', $request->user()->id)->first();
        if (!$shop) {
            abort(redirect('/registrar-local')->with('info', 'Primero registrá tu local.'));
        }
        return $shop;
    }

    public function edit(Request $request)
    {
        $shop = $this->getOwnerShop($request);

        return view('shop_dash.settings', [
            'pageTitle' => 'Configuración — ' . $shop->name, 'shop' => $shop,
            'hours' => ShopHour::where('shop_id', $shop->id)->orderBy('day_of_week')->get(),
            'photos' => ShopPhoto::where('shop_id', $shop->id)->orderBy('sort_order')->get(),
        ]);
    }

    public function update(Request $request)
    {
        $shop = $this->getOwnerShop($request);

        $validated = $request->validate([
            'name' => 'required|string|min:2|max:120',
            'city' => 'required|string|max:100',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email',
            'type' => 'nullable|in:barbershop,salon,mixed,nails,spa,tattoo,makeup,other',
            'target_audience' => 'nullable|in:men,women,unisex',
            'logo' => 'nullable|image|max:5120',
            'cover_image' => 'nullable|image|max:5120',
        ]);

        $data = [
            'name' => strip_tags($validated['name']),
            'type' => $validated['type'] ?? 'barbershop',
            'target_audience' => $validated['target_audience'] ?? 'unisex',
            'description' => strip_tags($request->input('description', '')),
            'specialties' => strip_tags($request->input('specialties', '')),
            'amenities' => strip_tags($request->input('amenities', '')),
            'phone' => strip_tags($validated['phone'] ?? ''),
            'email' => strtolower(trim($validated['email'] ?? '')),
            'website' => trim($request->input('website', '')),
            'instagram' => ltrim(trim($request->input('instagram', '')), '@'),
            'facebook' => trim($request->input('facebook', '')),
            'whatsapp' => strip_tags($request->input('whatsapp', '')),
            'address' => strip_tags($request->input('address', '')),
            'city' => strip_tags($validated['city']),
            'province' => strip_tags($request->input('province', '')),
            'postal_code' => strip_tags($request->input('postal_code', '')),
            'latitude' => $request->input('latitude', '') !== '' ? (float) $request->input('latitude') : null,
            'longitude' => $request->input('longitude', '') !== '' ? (float) $request->input('longitude') : null,
        ];

        if ($request->hasFile('logo')) {
            $p = ImageUploader::upload($request->file('logo'), 'shops');
            if ($p) $data['logo'] = $p;
        }
        if ($request->hasFile('cover_image')) {
            $p = ImageUploader::upload($request->file('cover_image'), 'shops');
            if ($p) $data['cover_image'] = $p;
        }

        $shop->update($data);

        return redirect('/panel/local')->with('success', 'Información actualizada.');
    }

    public function saveHours(Request $request)
    {
        $shop = $this->getOwnerShop($request);
        $days = $request->input('days', []);
        $open = $request->input('opens_at', []);
        $close = $request->input('closes_at', []);
        $bStart = $request->input('break_start', []);
        $bEnd = $request->input('break_end', []);

        ShopHour::where('shop_id', $shop->id)->delete();
        for ($d = 0; $d <= 6; $d++) {
            ShopHour::create([
                'shop_id' => $shop->id, 'day_of_week' => $d,
                'opens_at' => isset($days[$d]) ? ($open[$d] ?? null) : null,
                'closes_at' => isset($days[$d]) ? ($close[$d] ?? null) : null,
                'break_start' => $bStart[$d] ?? null, 'break_end' => $bEnd[$d] ?? null,
            ]);
        }

        return redirect('/panel/local')->with('success', 'Horarios guardados.');
    }

    public function uploadPhoto(Request $request)
    {
        $shop = $this->getOwnerShop($request);

        if (!$request->hasFile('photo')) {
            return response()->json(['error' => 'No se recibió ningún archivo'], 400);
        }
        $path = ImageUploader::upload($request->file('photo'), 'shops');
        if (!$path) {
            return response()->json(['error' => 'Archivo no válido (JPG, PNG, WebP — máx. 5 MB)'], 400);
        }

        $order = (ShopPhoto::where('shop_id', $shop->id)->max('sort_order') ?? 0) + 1;
        $photo = ShopPhoto::create([
            'shop_id' => $shop->id, 'filename' => $path,
            'caption' => strip_tags($request->input('caption', '')), 'sort_order' => $order,
        ]);

        return response()->json(['success' => true, 'photo' => $photo, 'url' => Storage::url($path)]);
    }

    public function deletePhoto(int $id, Request $request)
    {
        $shop = $this->getOwnerShop($request);
        $photo = ShopPhoto::where('id', $id)->where('shop_id', $shop->id)->first();

        if (!$photo) return response()->json(['error' => 'Foto no encontrada'], 404);

        ImageUploader::delete($photo->filename);
        $photo->delete();

        return response()->json(['success' => true]);
    }
}
