<?php

namespace App\Http\Controllers;

use App\Mail\BookingConfirmationMail;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Service;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    // ── Paso 1: elegir servicio ──────────────────────────────
    public function step1(string $slug, Request $request)
    {
        $shop = Shop::where('slug', $slug)->first();
        if (!$shop || $shop->status !== 'active') return redirect('/');

        if ($request->session()->get('booking.shop_id') !== $shop->id) {
            $request->session()->forget('booking');
        }

        return view('booking.step1', [
            'pageTitle' => 'Reservar turno — ' . $shop->name,
            'shop' => $shop,
            'services' => Service::getGroupedByCategory($shop->id),
            'booking' => $request->session()->get('booking', []),
        ]);
    }

    public function setService(string $slug, Request $request)
    {
        $shop = Shop::where('slug', $slug)->first();
        if (!$shop) return redirect('/');

        $service = Service::find($request->input('service_id'));
        if (!$service || $service->shop_id !== $shop->id) {
            return back()->with('error', 'Servicio no válido.');
        }

        $request->session()->put('booking', [
            'shop_id' => $shop->id, 'shop_slug' => $slug,
            'service_id' => $service->id,
        ]);

        return redirect("/reservar/{$slug}/empleado");
    }

    // ── Paso 2: elegir empleado ───────────────────────────────
    public function setEmployee(string $slug, Request $request)
    {
        $redirect = $this->requireBookingStep($slug, 'service_id', $request);
        if ($redirect) return $redirect;

        $shop = Shop::where('slug', $slug)->first();
        $booking = $request->session()->get('booking');

        if ($request->isMethod('post')) {
            $employeeId = (int) $request->input('employee_id');

            if ($employeeId === 0) {
                $request->session()->put('booking.employee_id', 0);
                $request->session()->put('booking.employee_name', 'Cualquiera disponible');
            } else {
                $employee = Employee::find($employeeId);
                if (!$employee || $employee->shop_id !== $shop->id) {
                    return back()->with('error', 'Empleado no válido.');
                }
                $request->session()->put('booking.employee_id', $employee->id);
                $request->session()->put('booking.employee_name', $employee->name);
            }

            return redirect("/reservar/{$slug}/horario");
        }

        $employees = Employee::availableForService($shop->id, $booking['service_id']);

        return view('booking.step2', [
            'pageTitle' => 'Elegir profesional — ' . $shop->name,
            'shop' => $shop, 'employees' => $employees, 'booking' => $booking,
        ]);
    }

    // ── Paso 3: elegir fecha y horario ────────────────────────
    public function step3(string $slug, Request $request)
    {
        $redirect = $this->requireBookingStep($slug, 'employee_id', $request);
        if ($redirect) return $redirect;

        $shop = Shop::where('slug', $slug)->first();

        return view('booking.step3', [
            'pageTitle' => 'Elegir horario — ' . $shop->name,
            'shop' => $shop, 'booking' => $request->session()->get('booking'),
        ]);
    }

    public function setSlot(string $slug, Request $request)
    {
        $redirect = $this->requireBookingStep($slug, 'employee_id', $request);
        if ($redirect) return $redirect;

        $date = $request->input('date');
        $start = $request->input('start_time');

        if (!$date || !$start) {
            return back()->with('error', 'Seleccioná fecha y horario.');
        }
        if (strtotime($date) < strtotime('today')) {
            return back()->with('error', 'La fecha no puede ser en el pasado.');
        }
        if ($date === date('Y-m-d') && strtotime("{$date} {$start}") < (time() + 5 * 60)) {
            return back()->with('error', 'Ese horario ya pasó. Por favor elegí uno futuro.');
        }

        $request->session()->put('booking.date', $date);
        $request->session()->put('booking.start_time', $start);

        return redirect("/reservar/{$slug}/confirmar");
    }

    // ── Paso 4: confirmación ──────────────────────────────────
    public function step4(string $slug, Request $request)
    {
        $redirect = $this->requireBookingStep($slug, 'start_time', $request);
        if ($redirect) return $redirect;

        $shop = Shop::where('slug', $slug)->first();
        $booking = $request->session()->get('booking');

        $service = Service::find($booking['service_id'] ?? 0);
        if (!$service) {
            return redirect("/reservar/{$slug}")->with('error', 'El servicio seleccionado ya no está disponible.');
        }
        $booking['service'] = $service;
        $depositPct = (int) ($service->deposit_pct ?? 0);
        $depositAmt = $depositPct > 0 ? round($service->price * $depositPct / 100, 2) : 0;

        return view('booking.step4', [
            'pageTitle' => 'Confirmar turno — ' . $shop->name,
            'shop' => $shop, 'booking' => $booking,
            'currentUser' => $request->user(),
            'depositPct' => $depositPct, 'depositAmt' => $depositAmt,
        ]);
    }

    public function confirm(string $slug, Request $request)
    {
        $redirect = $this->requireBookingStep($slug, 'start_time', $request);
        if ($redirect) return $redirect;

        $shop = Shop::where('slug', $slug)->first();
        $booking = $request->session()->get('booking');

        $currentUser = $request->user();
        if ($currentUser && !$currentUser->isClient()) {
            return redirect("/reservar/{$slug}")->with('error', 'Las cuentas de negocio o empleado no pueden reservar turnos.');
        }

        $validated = $request->validate([
            'client_name' => 'required|string|min:2|max:100',
            'client_email' => 'required|email',
            'client_phone' => 'nullable|string|max:30',
            'payment_option' => 'required|in:online,on_site,deposit',
            'notes' => 'nullable|string',
        ]);

        $service = Service::find($booking['service_id']);
        $empId = (int) $booking['employee_id'];

        if ($empId === 0) {
            $avail = Employee::availableForService($shop->id, $service->id);
            if ($avail->isEmpty()) {
                return redirect("/reservar/{$slug}")->with('error', 'No hay profesionales disponibles en este momento.');
            }
            $empId = $avail->first()->id;
        }

        $date = $booking['date'];
        $startTime = $booking['start_time'];
        $endTime = date('H:i', strtotime($startTime) + $service->duration_min * 60);

        if (Appointment::isSlotTaken($empId, $date, $startTime, $endTime)) {
            return redirect("/reservar/{$slug}/horario")->with('error', 'Ese horario ya fue tomado. Por favor elegí otro.');
        }

        $clientEmail = strtolower(trim($validated['client_email']));
        $clientName = strip_tags($validated['client_name']);
        $clientPhone = strip_tags($validated['client_phone'] ?? '');

        $client = Client::where('email', $clientEmail)->first();
        if ($client) {
            $client->update(['name' => $clientName, 'phone' => $clientPhone]);
        } else {
            $client = Client::create(['name' => $clientName, 'email' => $clientEmail, 'phone' => $clientPhone]);
        }

        $depositAmount = 0;
        if ($validated['payment_option'] === 'deposit' && ($service->deposit_pct ?? 0) > 0) {
            $depositAmount = round($service->price * $service->deposit_pct / 100, 2);
        }

        $token = Str::random(40);
        $cancelToken = Str::random(40);

        $appointment = Appointment::create([
            'shop_id' => $shop->id, 'employee_id' => $empId, 'service_id' => $service->id,
            'client_id' => $client->id, 'client_name' => $clientName, 'client_email' => $clientEmail,
            'client_phone' => $clientPhone, 'date' => $date, 'start_time' => $startTime, 'end_time' => $endTime,
            'duration_min' => $service->duration_min, 'price' => $service->price, 'deposit_amount' => $depositAmount,
            'status' => 'pending', 'payment_option' => $validated['payment_option'], 'payment_status' => 'unpaid',
            'notes' => strip_tags($validated['notes'] ?? ''), 'confirm_token' => $token, 'cancel_token' => $cancelToken,
        ]);

        try {
            Mail::to($clientEmail)->send(new BookingConfirmationMail($appointment, $shop));
        } catch (\Throwable $e) {
            // No bloqueamos la reserva si falla el envío de email
        }

        $request->session()->forget('booking');

        return redirect("/reservar/gracias/{$token}");
    }

    // ── Gracias ────────────────────────────────────────────────
    public function thanks(string $token)
    {
        $appointment = Appointment::where('confirm_token', $token)->first();
        if (!$appointment) return redirect('/');

        return view('booking.thanks', [
            'pageTitle' => '¡Turno reservado!',
            'appointment' => $appointment,
            'shop' => $appointment->shop,
            'service' => $appointment->service,
            'employee' => $appointment->employee,
        ]);
    }

    // ── APIs AJAX ──────────────────────────────────────────────
    public function apiEmployeesForService(Request $request)
    {
        $shop = Shop::where('slug', $request->query('shop_slug', ''))->first();
        $serviceId = (int) $request->query('service_id', 0);

        if (!$shop || !$serviceId) return response()->json(['employees' => []]);

        return response()->json(['employees' => Employee::availableForService($shop->id, $serviceId)]);
    }

    public function apiSlots(Request $request)
    {
        $employeeId = (int) $request->query('employee_id', 0);
        $serviceId = (int) $request->query('service_id', 0);
        $date = $request->query('date', '');

        if (!$employeeId || !$serviceId || !$date) return response()->json(['slots' => []]);
        if (strtotime($date) < strtotime('today')) return response()->json(['slots' => [], 'error' => 'Fecha pasada']);

        return response()->json([
            'slots' => Appointment::getAvailableSlots($employeeId, $serviceId, $date),
            'date' => $date,
        ]);
    }

    // ── Cancelar por token ─────────────────────────────────────
    public function cancelByToken(string $token)
    {
        $appointment = Appointment::where('cancel_token', $token)->first();
        if (!$appointment) return redirect('/')->with('error', 'Token de cancelación inválido.');

        if (in_array($appointment->status, ['cancelled_client', 'cancelled_shop', 'completed'])) {
            return redirect('/')->with('info', 'Este turno ya está cancelado o completado.');
        }

        if (strtotime("{$appointment->date->format('Y-m-d')} {$appointment->start_time}") < time()) {
            return redirect('/')->with('error', 'No podés cancelar un turno pasado.');
        }

        $appointment->update(['status' => 'cancelled_client']);

        return view('booking.cancelled', [
            'pageTitle' => 'Turno cancelado',
            'appointment' => $appointment,
            'shop' => $appointment->shop,
        ]);
    }

    private function requireBookingStep(string $slug, string $requiredKey, Request $request)
    {
        $booking = $request->session()->get('booking', []);
        if (empty($booking['shop_id']) || !isset($booking[$requiredKey])) {
            return redirect("/reservar/{$slug}");
        }
        return null;
    }
}
