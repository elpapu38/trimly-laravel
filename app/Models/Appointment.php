<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Appointment extends Model
{
    protected $fillable = [
        'shop_id', 'employee_id', 'service_id', 'client_id', 'client_name',
        'client_email', 'client_phone', 'date', 'start_time', 'end_time',
        'duration_min', 'price', 'deposit_amount', 'status', 'payment_option',
        'payment_status', 'payment_ref', 'notes', 'internal_notes',
        'reminder_sent', 'confirm_token', 'cancel_token',
    ];

    protected $casts = [
        'date' => 'date',
        'price' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'reminder_sent' => 'boolean',
    ];

    public function shop() { return $this->belongsTo(Shop::class); }
    public function employee() { return $this->belongsTo(Employee::class); }
    public function service() { return $this->belongsTo(Service::class); }
    public function client() { return $this->belongsTo(Client::class); }
    public function review() { return $this->hasOne(Review::class); }

    /**
     * Calcula los horarios disponibles para un empleado + servicio en una fecha dada.
     * Traducción directa de Appointment::getAvailableSlots() del sistema original.
     */
    public static function getAvailableSlots(int $employeeId, int $serviceId, string $date): array
    {
        if (strtotime($date) < strtotime(date('Y-m-d'))) {
            return [];
        }

        $dow = Carbon::parse($date)->dayOfWeek; // 0=domingo ... 6=sábado (igual que DAYOFWEEK()-1 en MySQL)

        $service = Service::find($serviceId);
        if (!$service) return [];

        $employeeHour = EmployeeHour::where('employee_id', $employeeId)->where('day_of_week', $dow)->first();
        $shopHour = null;
        $employee = Employee::find($employeeId);
        if ($employee) {
            $shopHour = ShopHour::where('shop_id', $employee->shop_id)->where('day_of_week', $dow)->first();
        }

        $opens = $employeeHour->opens_at ?? $shopHour->opens_at ?? null;
        $closes = $employeeHour->closes_at ?? $shopHour->closes_at ?? null;
        $duration = (int) $service->duration_min;

        if (!$opens || !$closes || $duration <= 0) return [];

        $busy = static::where('employee_id', $employeeId)->where('date', $date)
            ->whereNotIn('status', ['cancelled_client', 'cancelled_shop'])
            ->get(['start_time', 'end_time']);

        $blocks = ScheduleBlock::where(function ($q) use ($employeeId) {
            $q->where('employee_id', $employeeId)->orWhereNull('employee_id');
        })->where('date', $date)->get(['start_time', 'end_time']);

        $occupied = $busy->concat($blocks);

        $slots = [];
        $current = strtotime("{$date} {$opens}");
        $end = strtotime("{$date} {$closes}") - $duration * 60;

        if ($date === date('Y-m-d')) {
            $minStart = time() + 5 * 60;
            $rounded = (int) (ceil($minStart / (15 * 60)) * (15 * 60));
            if ($rounded > $current) $current = $rounded;
        }

        while ($current <= $end) {
            $ss = date('H:i', $current);
            $se = date('H:i', $current + $duration * 60);
            $ok = true;
            foreach ($occupied as $o) {
                if ($ss < $o->end_time && $se > $o->start_time) { $ok = false; break; }
            }
            if ($ok) $slots[] = ['start' => $ss, 'end' => $se];
            $current += 15 * 60;
        }

        return $slots;
    }

    public static function isSlotTaken(int $employeeId, string $date, string $start, string $end, ?int $excludeId = null): bool
    {
        $q = static::where('employee_id', $employeeId)->where('date', $date)
            ->whereNotIn('status', ['cancelled_client', 'cancelled_shop'])
            ->where('start_time', '<', $end)->where('end_time', '>', $start);
        if ($excludeId) $q->where('id', '!=', $excludeId);
        return $q->exists();
    }

    public function scopeForClientEmail($q, string $email)
    {
        return $q->where('client_email', strtolower(trim($email)));
    }
}
