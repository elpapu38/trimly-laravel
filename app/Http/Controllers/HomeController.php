<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Shop;
use App\Models\ShopPhoto;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        $featured = Shop::publicVisible()
            ->where('status', 'active')
            ->orderByDesc('featured')->orderByDesc('rating_avg')->orderByDesc('rating_count')
            ->limit(6)->get()
            ->map(function ($shop) {
                $shop->first_photo = ShopPhoto::where('shop_id', $shop->id)->orderBy('sort_order')->value('filename');
                return $shop;
            });

        $cities = Shop::where('status', 'active')->where('is_shadowbanned', false)
            ->whereNotNull('city')
            ->selectRaw('city, count(*) as total')
            ->groupBy('city')->orderByDesc('total')->limit(8)->get();

        $stats = [
            'shops' => Shop::where('status', 'active')->where('is_shadowbanned', false)->count(),
            'cities' => $cities->count(),
            'appointments' => Appointment::whereIn('status', ['confirmed', 'completed'])->count(),
        ];

        return view('home.index', compact('featured', 'cities', 'stats'))
            ->with('pageTitle', 'Trimly — Encontrá tu turno');
    }

    public function search(Request $request)
    {
        $query = trim((string) $request->query('q', ''));
        $city = trim((string) $request->query('ciudad', ''));
        $type = trim((string) $request->query('tipo', ''));
        $audience = trim((string) $request->query('audiencia', ''));
        $page = max(1, (int) $request->query('pagina', 1));
        $perPage = 9;

        $validTypes = ['barbershop', 'salon', 'mixed', 'nails', 'spa', 'tattoo', 'makeup', 'other', ''];
        if (!in_array($type, $validTypes, true)) $type = '';
        if (!in_array($audience, ['men', 'women', 'unisex', ''], true)) $audience = '';

        $q = Shop::publicVisible();
        if ($query !== '') {
            $q->where(function ($w) use ($query) {
                $w->where('name', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%")
                  ->orWhere('city', 'like', "%{$query}%");
            });
        }
        if ($city !== '') $q->where('city', $city);
        if ($type !== '') $q->where('type', $type);
        if ($audience !== '') $q->where('target_audience', $audience);

        $results = $q->orderByDesc('featured')->orderByDesc('rating_avg')->orderByDesc('rating_count')
            ->paginate($perPage, ['*'], 'pagina', $page);

        $cities = Shop::where('status', 'active')->where('is_shadowbanned', false)
            ->whereNotNull('city')->selectRaw('city, count(*) as total')
            ->groupBy('city')->orderByDesc('total')->limit(20)->get();

        return view('home.search', [
            'data' => $results->items(),
            'total' => $results->total(),
            'per_page' => $perPage,
            'current_page' => $results->currentPage(),
            'last_page' => $results->lastPage(),
            'pageTitle' => 'Buscar locales — Trimly',
            'query' => $query, 'city' => $city, 'type' => $type, 'audience' => $audience,
            'cities' => $cities,
        ]);
    }
}
