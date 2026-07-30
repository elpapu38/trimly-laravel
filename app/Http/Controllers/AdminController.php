<?php

namespace App\Http\Controllers;

use App\Mail\ShopApprovedMail;
use App\Models\Appointment;
use App\Models\Review;
use App\Models\Shop;
use App\Models\ShopModerationLog;
use App\Models\StatPeriod;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class AdminController extends Controller
{
    public function index()
    {
        $stats = [
            'total_shops' => Shop::count(),
            'active_shops' => Shop::where('status', 'active')->where('is_shadowbanned', false)->count(),
            'pending_shops' => Shop::where('status', 'pending')->count(),
            'suspended_shops' => Shop::where('status', 'suspended')->count(),
            'shadowbanned' => Shop::where('is_shadowbanned', true)->count(),
            'total_users' => User::where('role', 'client')->count(),
            'total_owners' => User::where('role', 'shop_owner')->count(),
            'total_appts' => Appointment::count(),
            'month_appts' => Appointment::whereMonth('date', now()->month)->whereYear('date', now()->year)->count(),
            'month_revenue' => Appointment::where('status', 'completed')
                ->whereMonth('date', now()->month)->whereYear('date', now()->year)->sum('price'),
            'flagged_reviews' => Review::where('report_count', '>', 0)->where('is_visible', true)->count(),
        ];

        $pendingShops = Shop::where('status', 'pending')->paginate(8);
        $recentUsers = User::orderByDesc('created_at')->limit(8)->get();

        $revenue = Appointment::where('status', 'completed')->where('date', '>=', now()->subMonths(6))
            ->selectRaw("DATE_FORMAT(date, '%Y-%m') as ym, sum(price) as rev, count(*) as cnt")
            ->groupBy('ym')->orderBy('ym')->get();

        $topShops = Shop::where('status', 'active')
            ->leftJoin('appointments', function ($j) {
                $j->on('appointments.shop_id', '=', 'shops.id')
                  ->whereMonth('appointments.date', now()->month)
                  ->where('appointments.status', 'completed');
            })
            ->selectRaw('shops.id, shops.name, shops.city, shops.rating_avg, shops.views_count, count(appointments.id) as month_appts')
            ->groupBy('shops.id', 'shops.name', 'shops.city', 'shops.rating_avg', 'shops.views_count')
            ->orderByDesc('month_appts')->orderByDesc('shops.rating_avg')->limit(5)->get();

        $recentMod = ShopModerationLog::with(['shop', 'admin'])->orderByDesc('created_at')->limit(10)->get();

        return view('admin.index', compact('stats', 'pendingShops', 'recentUsers', 'revenue', 'topShops', 'recentMod'))
            ->with('pageTitle', 'Panel Admin — Trimly');
    }

    public function shops(Request $request)
    {
        $status = $request->query('status', '');
        $search = $request->query('q', '');
        $type = $request->query('type', '');

        $q = Shop::query();
        if ($status) $q->where('status', $status);
        if ($type) $q->where('type', $type);
        if ($search) $q->where(fn ($w) => $w->where('name', 'like', "%{$search}%")->orWhere('city', 'like', "%{$search}%"));

        $paginator = $q->orderByDesc('created_at')->paginate(20);

        return view('admin.shops', [
            'pageTitle' => 'Locales — Admin', 'shops' => $paginator->items(), 'pagination' => $paginator,
            'filter' => compact('status', 'search', 'type'),
        ]);
    }

    public function shopDetail(int $id)
    {
        $shop = Shop::find($id);
        if (!$shop) return redirect('/admin/locales')->with('error', 'Local no encontrado.');

        $stats = Appointment::where('shop_id', $id)
            ->selectRaw("count(*) as total, sum(case when status='completed' then 1 else 0 end) as completed, sum(case when status='completed' then price else 0 end) as revenue")
            ->first();

        $modLog = ShopModerationLog::where('shop_id', $id)->with('admin')->orderByDesc('created_at')->limit(30)->get();
        $reviews = Review::where('shop_id', $id)->where('is_visible', true)->with('appointment')->orderByDesc('created_at')->limit(5)->get();

        return view('admin.shop_detail', [
            'pageTitle' => $shop->name . ' — Admin', 'shop' => $shop, 'owner' => $shop->owner,
            'stats' => $stats, 'modLog' => $modLog, 'reviews' => $reviews,
        ]);
    }

    public function moderateShop(int $id, Request $request)
    {
        $shop = Shop::find($id);
        if (!$shop) return response()->json(['error' => 'Local no encontrado'], 404);

        $validated = $request->validate([
            'action' => 'required|in:approve,suspend,unsuspend,ban,unban,shadowban,unshadowban,feature,unfeature,verify,note',
            'reason' => 'nullable|string',
            'days' => 'nullable|integer',
        ]);

        $action = $validated['action'];
        $reason = strip_tags($validated['reason'] ?? '');
        $days = (int) ($validated['days'] ?? 0);
        $admin = $request->user();
        $updates = [];
        $logAction = '';
        $expiresAt = null;

        switch ($action) {
            case 'approve':
                $updates = ['status' => 'active'];
                $logAction = 'approved';
                try { Mail::to($shop->owner->email)->send(new ShopApprovedMail($shop, $shop->owner)); } catch (\Throwable $e) {}
                break;
            case 'suspend':
                $expiresAt = $days > 0 ? now()->addDays($days) : null;
                $updates = [
                    'status' => 'suspended', 'suspension_reason' => $reason,
                    'suspension_until' => $expiresAt, 'suspension_public' => (bool) $request->input('suspension_public', 1),
                ];
                $logAction = 'suspended';
                break;
            case 'unsuspend':
                $updates = ['status' => 'active', 'suspension_reason' => null, 'suspension_until' => null, 'suspension_public' => true];
                $logAction = 'unsuspended';
                break;
            case 'ban': $updates = ['status' => 'suspended', 'ban_reason' => $reason]; $logAction = 'banned'; break;
            case 'unban': $updates = ['status' => 'active', 'ban_reason' => null]; $logAction = 'unbanned'; break;
            case 'shadowban': $updates = ['is_shadowbanned' => true]; $logAction = 'shadowban'; break;
            case 'unshadowban': $updates = ['is_shadowbanned' => false]; $logAction = 'unshadowban'; break;
            case 'feature': $updates = ['featured' => true]; $logAction = 'featured'; break;
            case 'unfeature': $updates = ['featured' => false]; $logAction = 'unfeatured'; break;
            case 'verify': $updates = ['verified' => true]; $logAction = 'verified'; break;
            case 'note': $logAction = 'note'; break;
        }

        if ($updates) $shop->update($updates);

        ShopModerationLog::create([
            'shop_id' => $id, 'admin_id' => $admin->id, 'action' => $logAction,
            'reason' => $reason ?: null, 'duration_days' => $days ?: null, 'expires_at' => $expiresAt,
        ]);

        return response()->json(['success' => true, 'action' => $logAction]);
    }

    public function users(Request $request)
    {
        $role = $request->query('role', '');
        $status = $request->query('status', '');
        $search = $request->query('q', '');

        $q = User::query();
        if ($role) $q->where('role', $role);
        if ($status) $q->where('status', $status);
        if ($search) $q->where(fn ($w) => $w->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));

        $paginator = $q->orderByDesc('created_at')->paginate(25);

        return view('admin.users', [
            'pageTitle' => 'Usuarios — Admin', 'users' => $paginator->items(), 'pagination' => $paginator,
            'filter' => compact('role', 'status', 'search'),
        ]);
    }

    public function userStatus(int $id, Request $request)
    {
        $action = $request->input('action', $request->input('status', ''));
        $target = User::find($id);

        if (!$target || $target->role === 'superadmin') {
            return response()->json(['error' => 'No permitido'], 403);
        }

        $reason = strip_tags($request->input('reason', ''));
        $days = (int) $request->input('days', 0);

        if ($action === 'delete') {
            // Anonimizar en vez de borrar (preserva turnos y reseñas históricas)
            $target->update([
                'name' => '[eliminado]', 'email' => "deleted_{$id}@trimly.local",
                'phone' => null, 'avatar' => null, 'status' => 'banned',
                'ban_reason' => 'Cuenta eliminada por admin',
            ]);
            Appointment::where('client_email', $target->getOriginal('email'))
                ->orWhere('employee_id', $id)
                ->whereIn('status', ['pending', 'confirmed'])
                ->update(['status' => 'cancelled_shop']);

            return response()->json(['success' => true, 'action' => 'deleted']);
        }

        if (!in_array($action, ['active', 'suspended', 'banned'])) {
            return response()->json(['error' => 'Estado no válido'], 400);
        }

        if ($action === 'active') {
            $target->update(['status' => 'active', 'ban_reason' => null, 'suspended_until' => null]);
        } elseif ($action === 'suspended') {
            $until = $days > 0 ? now()->addDays($days) : null;
            $target->update(['status' => 'suspended', 'ban_reason' => $reason ?: null, 'suspended_until' => $until]);
        } elseif ($action === 'banned') {
            $target->update(['status' => 'banned', 'ban_reason' => $reason ?: null, 'suspended_until' => null]);
        }

        return response()->json(['success' => true, 'action' => $action]);
    }

    public function reviews(Request $request)
    {
        $page = max(1, (int) $request->query('page', 1));

        $paginator = Review::where('report_count', '>', 0)
            ->with('appointment', 'shop')
            ->orderByDesc('report_count')->orderByDesc('created_at')
            ->paginate(20, ['*'], 'page', $page);

        return view('admin.reviews', [
            'pageTitle' => 'Reseñas reportadas', 'reviews' => $paginator->items(),
            'total' => $paginator->total(), 'page' => $page, 'lastPage' => $paginator->lastPage(),
        ]);
    }

    public function moderateReview(int $id, Request $request)
    {
        $action = $request->input('action');
        $review = Review::find($id);

        if ($action === 'hide') {
            $review?->update(['is_visible' => false]);
        } elseif ($action === 'show') {
            $review?->update(['is_visible' => true, 'report_count' => 0, 'flagged_at' => null]);
        } elseif ($action === 'delete') {
            $review?->delete();
        } elseif ($action === 'warn') {
            $reason = strip_tags($request->input('reason', 'Tu reseña fue reportada y revisada por el equipo de Trimly.'));
            if ($review && $review->appointment) {
                $email = $review->appointment->client_email;
                $user = User::where('email', $email)->first();
                if ($user) {
                    $user->update(['ban_reason' => trim(($user->ban_reason ?? '') . ' | ADVERTENCIA: ' . $reason)]);
                }
                $review->update(['is_visible' => false]);
            }
        }

        return response()->json(['success' => true]);
    }

    // ── Períodos de estadísticas personalizados ─────────────────
    public function statPeriods()
    {
        $periods = StatPeriod::with('creator')->orderByDesc('date_from')->get();

        return view('admin.stat_periods', ['pageTitle' => 'Períodos de estadísticas', 'periods' => $periods]);
    }

    public function createStatPeriod(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
        ]);

        $period = StatPeriod::create([
            'name' => strip_tags($validated['name']), 'date_from' => $validated['date_from'],
            'date_to' => $validated['date_to'], 'created_by' => $request->user()->id,
        ]);

        return response()->json(['success' => true, 'id' => $period->id]);
    }

    public function deleteStatPeriod(int $id)
    {
        StatPeriod::destroy($id);

        return response()->json(['success' => true]);
    }

    public function stats(Request $request)
    {
        $year = (int) $request->query('year', now()->year);

        $monthly = Appointment::whereYear('date', $year)
            ->selectRaw("MONTH(date) as month, count(*) as total, sum(case when status='completed' then 1 else 0 end) as completed, sum(case when status='completed' then price else 0 end) as revenue")
            ->groupBy(DB::raw('MONTH(date)'))->orderBy('month')->get()->keyBy('month');

        $byMonth = [];
        for ($m = 1; $m <= 12; $m++) {
            $byMonth[] = $monthly->get($m) ?? (object) ['month' => 0, 'total' => 0, 'completed' => 0, 'revenue' => 0];
        }

        $topShops = Appointment::where('status', 'completed')->whereYear('date', $year)
            ->join('shops', 'shops.id', '=', 'appointments.shop_id')
            ->selectRaw('shops.name, shops.city, shops.type, count(*) as total, sum(appointments.price) as revenue')
            ->groupBy('shops.id', 'shops.name', 'shops.city', 'shops.type')
            ->orderByDesc('revenue')->limit(10)->get();

        $byType = Shop::where('status', 'active')->selectRaw('type, count(*) as cnt')->groupBy('type')->orderByDesc('cnt')->get();
        $byCity = Shop::where('status', 'active')->whereNotNull('city')->selectRaw('city, count(*) as cnt')->groupBy('city')->orderByDesc('cnt')->limit(10)->get();
        $growth = User::where('created_at', '>=', now()->subMonths(12))
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, count(*) as new_users")
            ->groupBy('ym')->orderBy('ym')->get();

        return view('admin.stats', [
            'pageTitle' => 'Estadísticas globales', 'year' => $year, 'byMonth' => $byMonth,
            'topShops' => $topShops, 'byType' => $byType, 'byCity' => $byCity, 'growth' => $growth,
        ]);
    }
}
