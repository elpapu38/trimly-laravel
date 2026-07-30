<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\ShopReport;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function store(string $slug, Request $request)
    {
        $user = $request->user();

        if (in_array($user->role, ['shop_owner', 'employee', 'superadmin'])) {
            return response()->json(['error' => 'Tu tipo de cuenta no puede reportar locales.'], 403);
        }

        $shop = Shop::where('slug', $slug)->first();
        if (!$shop) return response()->json(['error' => 'Local no encontrado.'], 404);

        $validated = $request->validate([
            'reason' => 'required|in:spam,fake,offensive,closed,other',
            'note' => 'nullable|string',
        ]);

        $recent = ShopReport::where('shop_id', $shop->id)->where('user_id', $user->id)
            ->where('created_at', '>', now()->subDays(7))->exists();
        if ($recent) {
            return response()->json(['error' => 'Ya reportaste este local recientemente. Revisaremos tu reporte anterior.'], 400);
        }

        ShopReport::create([
            'shop_id' => $shop->id, 'user_id' => $user->id, 'reason' => $validated['reason'],
            'note' => isset($validated['note']) ? substr($validated['note'], 0, 500) : null,
        ]);

        return response()->json(['success' => true, 'message' => 'Gracias por tu reporte. Lo revisaremos a la brevedad.']);
    }
}
