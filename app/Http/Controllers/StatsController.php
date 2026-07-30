<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Shop;
use App\Models\StatPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatsController extends Controller
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

    public function index(Request $request)
    {
        $shop = $this->getOwnerShop($request);
        $allPeriods = StatPeriod::orderByDesc('date_from')->get();

        $periodId = $request->query('period', '');
        $year = (int) $request->query('year', now()->year);
        $dateFrom = null;
        $dateTo = null;
        $activePeriod = $periodId ? $allPeriods->firstWhere('id', (int) $periodId) : null;

        if (!$activePeriod && $allPeriods->isNotEmpty()) {
            $activePeriod = $allPeriods->first();
            $periodId = $activePeriod->id;
        }
        if ($activePeriod) {
            $dateFrom = $activePeriod->date_from;
            $dateTo = $activePeriod->date_to;
        }

        $baseQuery = Appointment::where('shop_id', $shop->id);
        if ($dateFrom && $dateTo) {
            $baseQuery->whereBetween('date', [$dateFrom, $dateTo]);
        } else {
            $baseQuery->whereYear('date', $year);
        }

        $monthlyData = (clone $baseQuery)
            ->selectRaw("MONTH(date) as month, count(*) as total_appts,
                sum(case when status='completed' then 1 else 0 end) as completed,
                sum(case when status='completed' then price else 0 end) as revenue,
                sum(case when status in ('cancelled_client','cancelled_shop') then 1 else 0 end) as cancelled")
            ->groupBy(DB::raw('MONTH(date)'))->orderBy('month')->get()->keyBy('month');

        $byMonth = [];
        for ($m = 1; $m <= 12; $m++) {
            $byMonth[] = $monthlyData->get($m) ?? (object) ['month' => 0, 'total_appts' => 0, 'completed' => 0, 'revenue' => 0, 'cancelled' => 0];
        }

        $topServices = Appointment::where('shop_id', $shop->id)->where('status', 'completed')
            ->whereYear('date', $year)->join('services', 'services.id', '=', 'appointments.service_id')
            ->selectRaw('services.name, count(*) as cnt, sum(appointments.price) as revenue')
            ->groupBy('services.id', 'services.name')->orderByDesc('cnt')->limit(8)->get();

        $topEmployees = Appointment::where('shop_id', $shop->id)->where('status', 'completed')
            ->whereYear('date', $year)->join('employees', 'employees.id', '=', 'appointments.employee_id')
            ->selectRaw('employees.name, count(*) as cnt, sum(appointments.price) as revenue')
            ->groupBy('employees.id', 'employees.name')->orderByDesc('cnt')->get();

        $totals = Appointment::where('shop_id', $shop->id)->whereYear('date', $year)
            ->selectRaw("count(*) as total,
                sum(case when status='completed' then 1 else 0 end) as completed,
                sum(case when status='completed' then price else 0 end) as revenue,
                sum(case when status in ('cancelled_client','cancelled_shop') then 1 else 0 end) as cancelled")
            ->first();

        return view('shop_dash.stats', [
            'allPeriods' => $allPeriods, 'activePeriod' => $activePeriod, 'periodId' => $periodId,
            'pageTitle' => 'Estadísticas — ' . $shop->name, 'shop' => $shop, 'year' => $year,
            'byMonth' => $byMonth, 'topServices' => $topServices, 'topEmployees' => $topEmployees, 'totals' => $totals,
        ]);
    }
}
