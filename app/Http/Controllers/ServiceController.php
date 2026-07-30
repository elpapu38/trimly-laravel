<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Shop;
use App\Services\ImageUploader;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    private function getOwnerShop(Request $request): Shop
    {
        $user = $request->user();
        $shop = Shop::where('owner_id', $user->id)->first();
        if (!$shop && $user->role === 'superadmin') {
            $shop = Shop::find((int) $request->session()->get('admin_shop_id', 0));
        }
        if (!$shop) {
            abort(redirect('/registrar-local')->with('info', 'Registrá tu local primero.'));
        }
        return $shop;
    }

    private function resolveCategory(Shop $shop, Request $request): ?int
    {
        $categoryId = (int) $request->input('category_id');
        $newCat = strip_tags($request->input('new_category', ''));
        if (!$categoryId && $newCat) {
            $cat = ServiceCategory::create(['shop_id' => $shop->id, 'name' => $newCat, 'sort_order' => 0]);
            return $cat->id;
        }
        return $categoryId ?: null;
    }

    public function index(Request $request)
    {
        $shop = $this->getOwnerShop($request);

        return view('shop_dash.services.index', [
            'pageTitle' => 'Servicios — ' . $shop->name, 'shop' => $shop,
            'grouped' => Service::getGroupedByCategory($shop->id),
            'categories' => ServiceCategory::where('shop_id', $shop->id)->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function create(Request $request)
    {
        $shop = $this->getOwnerShop($request);

        return view('shop_dash.services.form', [
            'pageTitle' => 'Nuevo servicio', 'shop' => $shop, 'service' => null,
            'categories' => ServiceCategory::where('shop_id', $shop->id)->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $shop = $this->getOwnerShop($request);

        $validated = $request->validate([
            'name' => 'required|string|min:2|max:120',
            'duration_min' => 'required|numeric',
            'price' => 'required|numeric',
            'image' => 'nullable|image|max:5120',
        ]);

        $data = [
            'shop_id' => $shop->id, 'category_id' => $this->resolveCategory($shop, $request),
            'name' => strip_tags($validated['name']), 'description' => strip_tags($request->input('description', '')),
            'duration_min' => (int) $validated['duration_min'], 'price' => (float) $validated['price'],
            'deposit_pct' => (int) $request->input('deposit_pct', 0), 'is_active' => true,
            'sort_order' => (int) $request->input('sort_order', 0),
        ];

        if ($request->hasFile('image')) {
            $img = ImageUploader::upload($request->file('image'), 'services');
            if ($img) $data['image'] = $img;
        }

        Service::create($data);

        return redirect('/panel/servicios')->with('success', 'Servicio creado correctamente.');
    }

    public function edit(int $id, Request $request)
    {
        $shop = $this->getOwnerShop($request);
        $service = Service::find($id);

        if (!$service || $service->shop_id !== $shop->id) {
            return redirect('/panel/servicios')->with('error', 'Servicio no encontrado.');
        }

        return view('shop_dash.services.form', [
            'pageTitle' => 'Editar — ' . $service->name, 'shop' => $shop, 'service' => $service,
            'categories' => ServiceCategory::where('shop_id', $shop->id)->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function update(int $id, Request $request)
    {
        $shop = $this->getOwnerShop($request);
        $service = Service::find($id);

        if (!$service || $service->shop_id !== $shop->id) {
            return redirect('/panel/servicios')->with('error', 'Servicio no encontrado.');
        }

        $validated = $request->validate([
            'name' => 'required|string|min:2|max:120',
            'duration_min' => 'required|numeric',
            'price' => 'required|numeric',
            'image' => 'nullable|image|max:5120',
        ]);

        $data = [
            'category_id' => $this->resolveCategory($shop, $request),
            'name' => strip_tags($validated['name']), 'description' => strip_tags($request->input('description', '')),
            'duration_min' => (int) $validated['duration_min'], 'price' => (float) $validated['price'],
            'deposit_pct' => (int) $request->input('deposit_pct', 0),
            'is_active' => (bool) $request->input('is_active', 1), 'sort_order' => (int) $request->input('sort_order', 0),
        ];

        if ($request->hasFile('image')) {
            $img = ImageUploader::upload($request->file('image'), 'services');
            if ($img) $data['image'] = $img;
        }

        $service->update($data);

        return redirect('/panel/servicios')->with('success', 'Servicio actualizado.');
    }

    public function delete(int $id, Request $request)
    {
        $shop = $this->getOwnerShop($request);
        $service = Service::find($id);

        if (!$service || $service->shop_id !== $shop->id) {
            return redirect('/panel/servicios')->with('error', 'Servicio no encontrado.');
        }

        $service->update(['is_active' => false]);

        return redirect('/panel/servicios')->with('success', 'Servicio desactivado.');
    }
}
