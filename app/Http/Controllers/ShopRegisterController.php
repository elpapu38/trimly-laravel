<?php

namespace App\Http\Controllers;

use App\Mail\ShopRegisteredMail;
use App\Models\Shop;
use App\Models\ShopHour;
use App\Services\ImageUploader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ShopRegisterController extends Controller
{
    public function form(Request $request)
    {
        $user = $request->user();

        if (Shop::where('owner_id', $user->id)->exists()) {
            return redirect('/panel')->with('info', 'Ya tenés un local registrado.');
        }

        return view('shop_register.form', ['pageTitle' => 'Registrar mi local']);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|min:2|max:120',
            'type' => 'required|in:barbershop,salon,mixed',
            'city' => 'required|string|max:100',
            'address' => 'required|string|max:255',
            'phone' => 'required|string|max:30',
            'logo' => 'nullable|image|max:5120',
        ]);

        $name = strip_tags($validated['name']);
        $shop = new Shop();
        $slug = $shop->generateSlug($name);

        $data = [
            'owner_id' => $user->id, 'name' => $name, 'slug' => $slug, 'type' => $validated['type'],
            'description' => strip_tags($request->input('description', '')),
            'phone' => strip_tags($validated['phone']),
            'email' => strtolower(trim($request->input('contact_email', $user->email))),
            'address' => strip_tags($validated['address']), 'city' => strip_tags($validated['city']),
            'province' => strip_tags($request->input('province', '')), 'country' => 'Argentina',
            'status' => 'pending', 'plan' => 'free',
        ];

        if ($request->hasFile('logo')) {
            $logo = ImageUploader::upload($request->file('logo'), 'shops');
            if ($logo) $data['logo'] = $logo;
        }

        $shop = Shop::create($data);

        $user->update(['role' => 'shop_owner']);

        // Horarios por defecto: lunes a sábado 9-18, domingo cerrado
        for ($d = 1; $d <= 6; $d++) {
            ShopHour::create(['shop_id' => $shop->id, 'day_of_week' => $d, 'opens_at' => '09:00:00', 'closes_at' => '18:00:00']);
        }
        ShopHour::create(['shop_id' => $shop->id, 'day_of_week' => 0, 'opens_at' => null, 'closes_at' => null]);

        try {
            Mail::to(config('mail.from.address'))->send(new ShopRegisteredMail($shop, $user));
        } catch (\Throwable $e) {
            // no bloqueamos el registro si falla el email
        }

        return redirect('/panel')->with('success', 'Tu local fue registrado. Está pendiente de aprobación por el administrador. Te avisaremos por email.');
    }
}
