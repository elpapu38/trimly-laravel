<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Employee;
use App\Models\Review;
use App\Models\Shop;
use App\Models\ShopModerationLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShopDashController extends Controller
{
    private function getOwnerShop(Request $request): Shop
    {
        $user = $request->user();

        if ($user->role === 'superadmin') {
            $shopId = $request->session()->get('admin_shop_id', 0);
            $shop = $shopId ? Shop::find($shopId) : null;
        } else {
            $shop = Shop::where('owner_id', $user->id)->first();
        }

        if (!$shop) {
            abort(redirect('/registrar-local')->with('info', 'Primero registrá tu local.'));
        }

        return $shop;
    }

    public function index(Request $request)
    {
        $shop = $this->getOwnerShop($request);
        $today = now()->toDateString();

        $todayAppts = Appointment::where('shop_id', $shop->id)->where('date', $today)
            ->with(['employee', 'service'])->orderBy('start_time')->get();
        $employees = Employee::where('shop_id', $shop->id)->orderBy('sort_order')->orderBy('name')->get();
        $recentReviews = Review::where('shop_id', $shop->id)->where('is_visible', true)
            ->with('appointment')->orderByDesc('created_at')->limit(5)->get();

        $monthStats = Appointment::where('shop_id', $shop->id)
            ->whereMonth('date', now()->month)->whereYear('date', now()->year)
            ->selectRaw("count(*) as total,
                sum(case when status='completed' then 1 else 0 end) as completed,
                sum(case when status in ('cancelled_client','cancelled_shop') then 1 else 0 end) as cancelled,
                sum(case when status='completed' then price else 0 end) as revenue")
            ->first();

        $adminNotes = ShopModerationLog::where('shop_id', $shop->id)
            ->whereIn('action', ['note', 'suspended', 'banned'])
            ->whereNull('dismissed_at')
            ->with('admin')->orderByDesc('created_at')->limit(10)->get();

        return view('shop_dash.index', [
            'pageTitle' => 'Panel — ' . $shop->name, 'shop' => $shop,
            'todayAppts' => $todayAppts, 'employees' => $employees,
            'recentReviews' => $recentReviews, 'monthStats' => $monthStats,
            'today' => $today, 'adminNotes' => $adminNotes,
        ]);
    }

    public function agenda(Request $request)
    {
        $shop = $this->getOwnerShop($request);
        $employees = Employee::where('shop_id', $shop->id)->orderBy('sort_order')->orderBy('name')->get();

        return view('shop_dash.agenda', ['pageTitle' => 'Agenda — ' . $shop->name, 'shop' => $shop, 'employees' => $employees]);
    }

    public function appointments(Request $request)
    {
        $shop = $this->getOwnerShop($request);
        $view = $request->query('view', 'day');
        $date = $request->query('date', now()->toDateString());
        $status = $request->query('status', '');
        $statuses = config('trimly.appointment_statuses', []);

        if ($view === 'history') {
            $page = max(1, (int) $request->query('page', 1));
            $q = Appointment::where('shop_id', $shop->id)->where('date', '<', now()->toDateString());
            if ($status) $q->where('status', $status);

            $paginator = $q->with(['service', 'employee'])
                ->orderByDesc('date')->orderByDesc('start_time')
                ->paginate(20, ['*'], 'page', $page);

            return view('shop_dash.appointments', [
                'pageTitle' => 'Historial — ' . $shop->name, 'shop' => $shop,
                'appointments' => $paginator->items(), 'selectedDate' => $date,
                'selectedStatus' => $status, 'view' => 'history',
                'total' => $paginator->total(), 'page' => $page, 'lastPage' => $paginator->lastPage(),
                'statuses' => $statuses,
            ]);
        }

        $q = Appointment::where('shop_id', $shop->id)->where('date', $date);
        if ($status) $q->where('status', $status);
        $appts = $q->with(['employee', 'service'])->orderBy('start_time')->get();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['appointments' => $appts]);
        }

        return view('shop_dash.appointments', [
            'pageTitle' => 'Turnos — ' . $shop->name, 'shop' => $shop, 'appointments' => $appts,
            'selectedDate' => $date, 'selectedStatus' => $status, 'view' => 'day', 'statuses' => $statuses,
        ]);
    }

    public function updateStatus(int $id, Request $request)
    {
        $shop = $this->getOwnerShop($request);
        $appointment = Appointment::find($id);

        if (!$appointment || $appointment->shop_id !== $shop->id) {
            return response()->json(['error' => 'Turno no encontrado'], 404);
        }

        $validated = $request->validate([
            'status' => 'required|in:confirmed,cancelled_shop,completed,no_show,pending',
            'internal_notes' => 'nullable|string',
        ]);

        $appointment->update([
            'status' => $validated['status'],
            'internal_notes' => strip_tags($validated['internal_notes'] ?? $appointment->internal_notes ?? ''),
        ]);

        if ($validated['status'] === 'completed') {
            $appointment->update(['reminder_sent' => true]);
        }

        return response()->json(['success' => true, 'status' => $validated['status']]);
    }

    public function agendaApi(Request $request)
    {
        $shop = $this->getOwnerShop($request);
        $start = $request->query('start', now()->startOfMonth()->toDateString());
        $end = $request->query('end', now()->endOfMonth()->toDateString());
        $empId = (int) $request->query('employee_id', 0);

        $q = Appointment::where('shop_id', $shop->id)->whereBetween('date', [$start, $end])
            ->whereNotIn('status', ['cancelled_client', 'cancelled_shop'])
            ->with(['employee', 'service']);
        if ($empId) $q->where('employee_id', $empId);

        $statusColors = ['pending' => '#C9A84C', 'confirmed' => '#22c55e', 'completed' => '#3b82f6', 'no_show' => '#6b7280'];

        $events = $q->get()->map(fn ($a) => [
            'id' => $a->id,
            'title' => "{$a->client_name} — {$a->service->name}",
            'start' => "{$a->date->format('Y-m-d')}T{$a->start_time}",
            'end' => "{$a->date->format('Y-m-d')}T{$a->end_time}",
            'backgroundColor' => $statusColors[$a->status] ?? '#C9A84C',
            'extendedProps' => [
                'employee' => $a->employee->name ?? '', 'status' => $a->status,
                'phone' => $a->client_phone, 'price' => $a->price, 'notes' => $a->notes ?? '',
            ],
        ]);

        return response()->json($events);
    }

    public function dismissNote(int $id, Request $request)
    {
        $user = $request->user();

        if ($user->role === 'superadmin') {
            $shopId = $request->session()->get('admin_shop_id');
        } else {
            $shopId = Shop::where('owner_id', $user->id)->value('id');
        }

        if (!$shopId) {
            return response()->json(['ok' => false, 'msg' => 'No se encontró el local asociado.'], 403);
        }

        $updated = ShopModerationLog::where('id', $id)->where('shop_id', $shopId)
            ->whereNull('dismissed_at')->update(['dismissed_at' => now()]);

        if (!$updated) {
            return response()->json(['ok' => false, 'msg' => 'El aviso ya no está disponible.'], 404);
        }

        return response()->json(['ok' => true]);
    }
}
