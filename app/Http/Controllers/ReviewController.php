<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\ReviewReport;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    private function getOwnerShop(Request $request): Shop
    {
        $user = $request->user();
        $shop = Shop::where('owner_id', $user->id)->first();
        if (!$shop && $user->role === 'superadmin') {
            $shop = Shop::find((int) $request->session()->get('admin_shop_id', 0));
        }
        if (!$shop) abort(redirect('/panel'));
        return $shop;
    }

    public function shopIndex(Request $request)
    {
        $shop = $this->getOwnerShop($request);
        $page = (int) $request->query('page', 1);

        $paginator = Review::where('shop_id', $shop->id)->where('is_visible', true)
            ->orderByDesc('created_at')->with('appointment')->paginate(15, ['*'], 'page', $page);

        return view('shop_dash.reviews', [
            'pageTitle' => 'Reseñas — ' . $shop->name, 'shop' => $shop,
            'reviews' => $paginator->items(), 'pagination' => $paginator,
        ]);
    }

    public function reply(int $id, Request $request)
    {
        $shop = $this->getOwnerShop($request);
        $review = Review::find($id);

        if (!$review || $review->shop_id !== $shop->id) {
            return response()->json(['error' => 'Reseña no encontrada'], 404);
        }

        $validated = $request->validate(['reply' => 'required|string|min:2']);
        $review->update(['reply' => strip_tags($validated['reply']), 'reply_at' => now()]);

        return response()->json(['success' => true, 'reply' => $review->reply]);
    }

    public function toggle(int $id, Request $request)
    {
        $shop = $this->getOwnerShop($request);
        $review = Review::find($id);

        if (!$review || $review->shop_id !== $shop->id) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $review->update(['is_visible' => !$review->is_visible]);
        $shop->updateRating();

        return response()->json(['success' => true, 'is_visible' => $review->is_visible]);
    }

    // ── POST /resena/reportar — cualquier usuario logueado ──────
    public function report(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'review_id' => 'required|integer',
            'reason' => 'required|in:spam,fake,offensive,irrelevant,other',
        ]);

        $review = Review::find($validated['review_id']);
        if (!$review || !$review->is_visible) {
            return response()->json(['error' => 'Reseña no encontrada.'], 404);
        }

        if (ReviewReport::where('review_id', $review->id)->where('user_id', $user->id)->exists()) {
            return response()->json(['error' => 'Ya reportaste esta reseña anteriormente.'], 400);
        }

        ReviewReport::create(['review_id' => $review->id, 'user_id' => $user->id, 'reason' => $validated['reason']]);

        $review->increment('report_count');
        if ($review->report_count >= 3 && !$review->flagged_at) {
            $review->update(['flagged_at' => now()]);
        }

        return response()->json(['success' => true, 'message' => 'Reseña reportada. El equipo la revisará pronto.']);
    }
}
