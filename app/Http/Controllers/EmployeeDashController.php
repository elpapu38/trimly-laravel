<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Employee;
use App\Models\EmployeePhoto;
use App\Models\Service;
use App\Models\User;
use App\Services\ImageUploader;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EmployeeDashController extends Controller
{
    /** Empleado vinculado al usuario logueado (con datos del local). */
    private function getEmployee(Request $request): Employee
    {
        $employee = Employee::with('shop')->where('user_id', $request->user()->id)
            ->where('status', 'active')->first();

        if (!$employee) {
            abort(redirect('/login')->with('error', 'Tu cuenta de empleado no está vinculada a ningún local activo.'));
        }

        return $employee;
    }

    private function checkShopSuspension(Employee $emp)
    {
        if (($emp->shop->status ?? 'active') === 'suspended') {
            return response()->view('employee_dash.suspended', ['pageTitle' => 'Local suspendido', 'employee' => $emp]);
        }
        return null;
    }

    public function index(Request $request)
    {
        $emp = $this->getEmployee($request);
        if ($resp = $this->checkShopSuspension($emp)) return $resp;

        $today = now()->toDateString();

        $todayAppts = Appointment::where('employee_id', $emp->id)->where('date', $today)
            ->whereNotIn('status', ['cancelled_client', 'cancelled_shop'])
            ->with('service')->orderBy('start_time')->get();

        $monthStats = Appointment::where('employee_id', $emp->id)
            ->whereMonth('date', now()->month)->whereYear('date', now()->year)
            ->selectRaw("count(*) as total, sum(case when status='completed' then 1 else 0 end) as completed, sum(case when status='completed' then price else 0 end) as revenue")
            ->first();

        return view('employee_dash.index', [
            'pageTitle' => 'Mi panel — ' . $emp->name, 'employee' => $emp,
            'todayAppts' => $todayAppts, 'monthStats' => $monthStats, 'today' => $today,
        ]);
    }

    public function appointments(Request $request)
    {
        $emp = $this->getEmployee($request);
        if ($resp = $this->checkShopSuspension($emp)) return $resp;

        $date = $request->query('date', now()->toDateString());
        $status = $request->query('status', '');
        $view = $request->query('view', 'day');
        $statuses = config('trimly.appointment_statuses', []);

        if ($view === 'history') {
            $page = max(1, (int) $request->query('page', 1));
            $q = Appointment::where('employee_id', $emp->id)->where('date', '<', now()->toDateString());
            if ($status) $q->where('status', $status);

            $paginator = $q->with('service')->orderByDesc('date')->orderByDesc('start_time')->paginate(20, ['*'], 'page', $page);

            return view('employee_dash.history', [
                'pageTitle' => 'Historial', 'employee' => $emp, 'appointments' => $paginator->items(),
                'selectedStatus' => $status, 'statuses' => $statuses,
                'total' => $paginator->total(), 'page' => $page, 'lastPage' => $paginator->lastPage(),
            ]);
        }

        $q = Appointment::where('employee_id', $emp->id)->where('date', $date);
        if ($status) $q->where('status', $status);
        $appts = $q->with('service')->orderBy('start_time')->get();

        $dayOfWeek = \Carbon\Carbon::parse($date)->dayOfWeekIso; // 1=lunes .. 7=domingo
        $weekStart = \Carbon\Carbon::parse($date)->subDays($dayOfWeek - 1)->toDateString();
        $weekEnd = \Carbon\Carbon::parse($weekStart)->addDays(6)->toDateString();

        $weekAppts = Appointment::where('employee_id', $emp->id)->whereBetween('date', [$weekStart, $weekEnd])
            ->whereNotIn('status', ['cancelled_client', 'cancelled_shop'])
            ->with('service')->orderBy('date')->orderBy('start_time')->get();
        $weekMap = $weekAppts->groupBy(fn ($a) => $a->date->format('Y-m-d'));

        $openAppts = Appointment::where('shop_id', $emp->shop_id)
            ->where(fn ($w) => $w->whereNull('employee_id')->orWhere('employee_id', 0))
            ->where('date', '>=', now()->toDateString())->whereIn('status', ['pending', 'confirmed'])
            ->with('service')->orderBy('date')->orderBy('start_time')->get();

        return view('employee_dash.appointments', [
            'pageTitle' => 'Mis turnos', 'employee' => $emp, 'appointments' => $appts,
            'selectedDate' => $date, 'selectedStatus' => $status, 'statuses' => $statuses,
            'weekMap' => $weekMap, 'weekStart' => $weekStart, 'weekEnd' => $weekEnd, 'openAppts' => $openAppts,
        ]);
    }

    public function updateStatus(int $id, Request $request)
    {
        $emp = $this->getEmployee($request);
        if (($emp->shop->status ?? 'active') === 'suspended') {
            return response()->json(['error' => 'El local está suspendido. No podés realizar esta acción.'], 403);
        }

        $appointment = Appointment::find($id);
        if (!$appointment || $appointment->employee_id !== $emp->id) {
            return response()->json(['error' => 'Turno no encontrado.'], 404);
        }

        $validated = $request->validate(['status' => 'required|in:confirmed,completed,no_show,cancelled_shop']);
        $appointment->update(['status' => $validated['status']]);

        return response()->json(['success' => true, 'status' => $validated['status']]);
    }

    public function newAppointment(Request $request)
    {
        $emp = $this->getEmployee($request);
        if ($resp = $this->checkShopSuspension($emp)) return $resp;

        $services = $emp->services()->where('is_active', true)->orderBy('name')->get();

        return view('employee_dash.new_appointment', ['pageTitle' => 'Cargar turno', 'employee' => $emp, 'services' => $services]);
    }

    public function storeAppointment(Request $request)
    {
        $emp = $this->getEmployee($request);

        $validated = $request->validate([
            'service_id' => 'required|integer', 'date' => 'required|date', 'start_time' => 'required',
            'client_name' => 'required|string', 'client_phone' => 'nullable|string', 'client_email' => 'nullable|email', 'notes' => 'nullable|string',
        ]);

        if (strtotime($validated['date']) < strtotime('today')) {
            return response()->json(['error' => 'La fecha no puede ser en el pasado.'], 400);
        }
        if ($validated['date'] === date('Y-m-d') && strtotime("{$validated['date']} {$validated['start_time']}") < time()) {
            return response()->json(['error' => 'El horario ya pasó.'], 400);
        }

        $service = Service::find($validated['service_id']);
        if (!$service) return response()->json(['error' => 'Servicio no válido.'], 400);

        $clientEmail = strtolower(strip_tags($validated['client_email'] ?? ''));
        if ($clientEmail) {
            $found = User::where('email', $clientEmail)->first();
            if ($found && in_array($found->role, ['superadmin', 'shop_owner', 'employee'])) {
                return response()->json(['error' => 'Ese usuario no puede recibir turnos (cuenta de negocio o empleado).'], 400);
            }
        }

        $endTime = date('H:i', strtotime($validated['start_time']) + $service->duration_min * 60);

        if (Appointment::isSlotTaken($emp->id, $validated['date'], $validated['start_time'], $endTime)) {
            return response()->json(['error' => 'Ya tenés un turno en ese horario.'], 400);
        }

        $appointment = Appointment::create([
            'shop_id' => $emp->shop_id, 'employee_id' => $emp->id, 'service_id' => $service->id,
            'client_name' => strip_tags($validated['client_name']),
            'client_email' => $clientEmail ?: 'manual@trimly.local',
            'client_phone' => strip_tags($validated['client_phone'] ?? ''),
            'date' => $validated['date'], 'start_time' => $validated['start_time'], 'end_time' => $endTime,
            'duration_min' => $service->duration_min, 'price' => $service->price, 'deposit_amount' => 0,
            'status' => 'confirmed', 'payment_option' => 'on_site', 'payment_status' => 'unpaid',
            'notes' => strip_tags($validated['notes'] ?? ''),
            'confirm_token' => Str::random(40), 'cancel_token' => Str::random(40),
        ]);

        return response()->json(['success' => true, 'id' => $appointment->id]);
    }

    public function services(Request $request)
    {
        $emp = $this->getEmployee($request);
        $allServices = Service::where('shop_id', $emp->shop_id)->where('is_active', true)->orderBy('name')->get();
        $assignedIds = $emp->services()->pluck('services.id')->toArray();

        return view('employee_dash.services', ['pageTitle' => 'Mis servicios', 'employee' => $emp, 'allServices' => $allServices, 'assignedIds' => $assignedIds]);
    }

    public function updateServices(Request $request)
    {
        $emp = $this->getEmployee($request);
        $serviceIds = array_map('intval', $request->input('service_ids', []));

        $validIds = $serviceIds ? Service::whereIn('id', $serviceIds)->where('shop_id', $emp->shop_id)->pluck('id')->toArray() : [];

        $emp->services()->sync($validIds);

        return response()->json(['success' => true, 'assigned' => count($validIds)]);
    }

    public function profile(Request $request)
    {
        $emp = $this->getEmployee($request);

        return view('employee_dash.profile', ['pageTitle' => 'Mi perfil', 'employee' => $emp]);
    }

    public function updateProfile(Request $request)
    {
        $emp = $this->getEmployee($request);
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|min:2|max:100', 'specialty' => 'nullable|string|max:150',
            'instagram' => 'nullable|string|max:100', 'avatar' => 'nullable|image|max:5120',
        ]);

        $data = [
            'name' => strip_tags($validated['name']), 'bio' => strip_tags($request->input('bio', '')),
            'specialty' => strip_tags($validated['specialty'] ?? ''),
            'instagram' => ltrim(trim($validated['instagram'] ?? ''), '@'),
        ];

        if ($request->hasFile('avatar')) {
            $avatar = ImageUploader::upload($request->file('avatar'), 'employees');
            if ($avatar) $data['avatar'] = $avatar;
        }

        $emp->update($data);
        $user->update(['name' => $data['name']]);

        return redirect('/mi-panel/perfil')->with('success', 'Perfil actualizado.');
    }

    public function photos(Request $request)
    {
        $emp = $this->getEmployee($request);
        $photos = EmployeePhoto::where('employee_id', $emp->id)->orderByDesc('created_at')->get();

        return view('employee_dash.photos', ['pageTitle' => 'Mis fotos', 'employee' => $emp, 'photos' => $photos]);
    }

    public function uploadPhoto(Request $request)
    {
        $emp = $this->getEmployee($request);

        if (!$request->hasFile('photo')) {
            return response()->json(['error' => 'No se recibió ningún archivo.'], 400);
        }
        if (EmployeePhoto::where('employee_id', $emp->id)->count() >= 12) {
            return response()->json(['error' => 'Ya alcanzaste el límite de 12 fotos.'], 400);
        }

        $path = ImageUploader::upload($request->file('photo'), 'employees');
        if (!$path) {
            return response()->json(['error' => 'Archivo no válido (JPG, PNG, WebP — máx. 5 MB).'], 400);
        }

        $photo = EmployeePhoto::create([
            'employee_id' => $emp->id, 'filename' => $path, 'caption' => strip_tags($request->input('caption', '')),
        ]);

        return response()->json(['success' => true, 'id' => $photo->id, 'url' => \Storage::url($path), 'filename' => $path]);
    }

    public function deletePhoto(int $id, Request $request)
    {
        $emp = $this->getEmployee($request);
        $photo = EmployeePhoto::where('id', $id)->where('employee_id', $emp->id)->first();

        if (!$photo) return response()->json(['error' => 'Foto no encontrada.'], 404);

        ImageUploader::delete($photo->filename);
        $photo->delete();

        return response()->json(['success' => true]);
    }
}
