<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Employee;
use App\Models\EmployeePhoto;
use App\Models\Review;
use App\Models\Service;
use App\Models\Shop;
use App\Models\ShopHour;
use App\Models\ShopPhoto;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function show(string $slug, Request $request)
    {
        $shop = Shop::where('slug', $slug)->first();

        $isSuspendedHidden = $shop && $shop->status === 'suspended' && !$shop->suspension_public;
        if (!$shop || $shop->status === 'closed' || $shop->is_shadowbanned || $isSuspendedHidden) {
            return response()->view('errors.404', [], 404);
        }

        // Track de vista, una vez por sesión
        $viewKey = 'view_' . $shop->id;
        if (!$request->session()->has($viewKey)) {
            $shop->increment('views_count');
            $request->session()->put($viewKey, time());
        }

        $photos = ShopPhoto::where('shop_id', $shop->id)->orderBy('sort_order')->get();
        $rawHours = ShopHour::where('shop_id', $shop->id)->orderBy('day_of_week')->get();
        $hoursByDay = $rawHours->keyBy('day_of_week');

        $servicesGrouped = Service::getGroupedByCategory($shop->id);
        $totalServices = array_sum(array_map('count', $servicesGrouped));

        $employees = Employee::where('shop_id', $shop->id)->active()
            ->orderBy('sort_order')->orderBy('name')->get();
        foreach ($employees as $emp) {
            $emp->portfolio = EmployeePhoto::where('employee_id', $emp->id)
                ->orderByDesc('created_at')->limit(12)->get();
        }

        $reviewSort = in_array($request->query('resenas', 'newest'), ['newest', 'highest', 'lowest'])
            ? $request->query('resenas', 'newest') : 'newest';

        $reviewsQuery = Review::where('shop_id', $shop->id)->where('is_visible', true);
        $orderBy = match ($reviewSort) {
            'highest' => ['rating', 'desc'],
            'lowest' => ['rating', 'asc'],
            default => ['created_at', 'desc'],
        };
        $reviewsPaginator = $reviewsQuery->orderBy(...$orderBy)->orderByDesc('created_at')
            ->with('appointment')->paginate(6, ['*'], 'pagina', 1);

        $ratingBreakdown = Review::ratingBreakdown($shop->id);
        $minPrice = Service::where('shop_id', $shop->id)->where('is_active', true)->min('price');

        $user = $request->user();
        $canReview = false;
        $isFavorite = false;
        if ($user) {
            $canReview = Appointment::where('shop_id', $shop->id)
                ->where('client_email', $user->email)->where('status', 'completed')
                ->doesntHave('review')->exists();
            $isFavorite = $shop->favoritedBy()->where('users.id', $user->id)->exists();
        }

        $todayDow = (int) Carbon::now()->dayOfWeek;
        $todayHours = $hoursByDay->get($todayDow);
        $isOpenNow = false;
        if ($todayHours && $todayHours->opens_at && $todayHours->closes_at) {
            $now = now()->format('H:i');
            $isOpenNow = $now >= $todayHours->opens_at && $now < $todayHours->closes_at;
        }

        return view('shop.show', [
            'shop' => $shop, 'photos' => $photos, 'hoursByDay' => $hoursByDay,
            'servicesGrouped' => $servicesGrouped, 'totalServices' => $totalServices,
            'employees' => $employees,
            'reviews' => [
                'data' => $reviewsPaginator->items(), 'total' => $reviewsPaginator->total(),
                'per_page' => 6, 'current_page' => $reviewsPaginator->currentPage(),
                'last_page' => $reviewsPaginator->lastPage(), 'sort' => $reviewSort,
            ],
            'ratingBreakdown' => $ratingBreakdown, 'minPrice' => $minPrice,
            'canReview' => $canReview, 'isOpenNow' => $isOpenNow, 'todayDow' => $todayDow,
            'isFavorite' => $isFavorite, 'user' => $user, 'reviewSort' => $reviewSort,
        ]);
    }

    public function reviews(string $slug, Request $request)
    {
        $shop = Shop::where('slug', $slug)->firstOrFail();
        $page = max(1, (int) $request->query('pagina', 2));
        $sort = in_array($request->query('orden', 'newest'), ['newest', 'highest', 'lowest'])
            ? $request->query('orden', 'newest') : 'newest';

        $orderBy = match ($sort) {
            'highest' => ['rating', 'desc'],
            'lowest' => ['rating', 'asc'],
            default => ['created_at', 'desc'],
        };
        $paginator = Review::where('shop_id', $shop->id)->where('is_visible', true)
            ->orderBy(...$orderBy)->with('appointment')->paginate(6, ['*'], 'pagina', $page);

        return response()->json([
            'data' => $paginator->items(), 'total' => $paginator->total(), 'per_page' => 6,
            'current_page' => $paginator->currentPage(), 'last_page' => $paginator->lastPage(), 'sort' => $sort,
        ]);
    }

    public function services(string $slug)
    {
        $shop = Shop::where('slug', $slug)->firstOrFail();
        return response()->json(Service::getGroupedByCategory($shop->id));
    }

    public function toggleFavorite(string $slug, Request $request)
    {
        $shop = Shop::where('slug', $slug)->firstOrFail();
        $user = $request->user();

        $exists = $shop->favoritedBy()->where('users.id', $user->id)->exists();
        if ($exists) {
            $shop->favoritedBy()->detach($user->id);
            return response()->json(['success' => true, 'favorited' => false]);
        }

        $shop->favoritedBy()->syncWithoutDetaching([$user->id]);
        return response()->json(['success' => true, 'favorited' => true]);
    }
}
