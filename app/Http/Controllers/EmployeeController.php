<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeHour;
use App\Models\Service;
use App\Models\Shop;
use App\Models\User;
use App\Services\ImageUploader;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    private function getOwnerShop(Request $request): Shop
    {
        $user = $request->user();
        $shop = Shop::where('owner_id', $user->id)->first();
        if (!$shop && $user->role === 'superadmin') {
            $shop = Shop::find((int) $request->session()->get('admin_shop_id', 0));
        }
        if (!$shop) {
            abort(redirect('/registrar-local')->with('info', 'Registrá tu local primero.'));
        }
        return $shop;
    }

    public function index(Request $request)
    {
        $shop = $this->getOwnerShop($request);
        $employees = Employee::with('services')->where('shop_id', $shop->id)->orderBy('sort_order')->orderBy('name')->get();

        return view('shop_dash.employees.index', ['pageTitle' => 'Empleados — ' . $shop->name, 'shop' => $shop, 'employees' => $employees]);
    }

    public function create(Request $request)
    {
        $shop = $this->getOwnerShop($request);
        $services = Service::where('shop_id', $shop->id)->active()->orderBy('sort_order')->orderBy('name')->get();

        return view('shop_dash.employees.form', ['pageTitle' => 'Nuevo empleado', 'shop' => $shop, 'employee' => null, 'services' => $services, 'assigned' => []]);
    }

    public function store(Request $request)
    {
        $shop = $this->getOwnerShop($request);

        $validated = $request->validate([
            'name' => 'required|string|min:2|max:100',
            'specialty' => 'nullable|string|max:150',
            'instagram' => 'nullable|string|max:100',
            'avatar' => 'nullable|image|max:5120',
        ]);

        $data = [
            'shop_id' => $shop->id, 'name' => strip_tags($validated['name']),
            'bio' => strip_tags($request->input('bio', '')),
            'specialty' => strip_tags($validated['specialty'] ?? ''),
            'instagram' => strip_tags($validated['instagram'] ?? ''),
            'status' => 'active', 'sort_order' => (int) $request->input('sort_order', 0),
        ];

        if ($request->hasFile('avatar')) {
            $avatar = ImageUploader::upload($request->file('avatar'), 'employees');
            if ($avatar) $data['avatar'] = $avatar;
        }

        $employee = Employee::create($data);
        $employee->services()->sync($request->input('service_ids', []));
        $this->saveEmployeeHours($employee->id, $request);

        return redirect('/panel/empleados')->with('success', 'Empleado creado correctamente.');
    }

    public function edit(int $id, Request $request)
    {
        $shop = $this->getOwnerShop($request);
        $employee = Employee::with('user')->find($id);

        if (!$employee || $employee->shop_id !== $shop->id) {
            return redirect('/panel/empleados')->with('error', 'Empleado no encontrado.');
        }

        $services = Service::where('shop_id', $shop->id)->active()->orderBy('sort_order')->orderBy('name')->get();
        $assignedIds = $employee->services()->pluck('services.id')->toArray();
        $hours = EmployeeHour::where('employee_id', $id)->orderBy('day_of_week')->get();

        return view('shop_dash.employees.form', [
            'pageTitle' => 'Editar — ' . $employee->name, 'shop' => $shop,
            'employee' => $employee, 'services' => $services, 'assigned' => $assignedIds, 'hours' => $hours,
        ]);
    }

    public function update(int $id, Request $request)
    {
        $shop = $this->getOwnerShop($request);
        $employee = Employee::find($id);

        if (!$employee || $employee->shop_id !== $shop->id) {
            return redirect('/panel/empleados')->with('error', 'Empleado no encontrado.');
        }

        $validated = $request->validate([
            'name' => 'required|string|min:2|max:100',
            'avatar' => 'nullable|image|max:5120',
        ]);

        $data = [
            'name' => strip_tags($validated['name']),
            'bio' => strip_tags($request->input('bio', '')),
            'specialty' => strip_tags($request->input('specialty', '')),
            'instagram' => strip_tags($request->input('instagram', '')),
            'status' => $request->input('status', 'active'),
            'sort_order' => (int) $request->input('sort_order', 0),
        ];

        if ($request->hasFile('avatar')) {
            $avatar = ImageUploader::upload($request->file('avatar'), 'employees');
            if ($avatar) $data['avatar'] = $avatar;
        }

        $employee->update($data);
        $employee->services()->sync($request->input('service_ids', []));
        $this->saveEmployeeHours($id, $request);

        return redirect('/panel/empleados')->with('success', 'Empleado actualizado.');
    }

    public function delete(int $id, Request $request)
    {
        $shop = $this->getOwnerShop($request);
        $employee = Employee::find($id);

        if (!$employee || $employee->shop_id !== $shop->id) {
            return redirect('/panel/empleados')->with('error', 'Empleado no encontrado.');
        }

        $employee->update(['status' => 'inactive']); // soft delete

        return redirect('/panel/empleados')->with('success', 'Empleado desactivado.');
    }

    public function linkAccount(int $id, Request $request)
    {
        $shop = $this->getOwnerShop($request);
        $employee = Employee::find($id);

        if (!$employee || $employee->shop_id !== $shop->id) {
            return response()->json(['error' => 'Empleado no encontrado.'], 404);
        }

        $email = strtolower(trim($request->input('email', '')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['error' => 'Email no válido.'], 400);
        }

        $user = User::where('email', $email)->where('status', 'active')->first();
        if (!$user) {
            return response()->json(['error' => 'No existe un usuario activo con ese email.'], 404);
        }
        if (!in_array($user->role, ['client', 'employee'])) {
            return response()->json(['error' => 'Ese usuario no puede ser vinculado como empleado.'], 400);
        }
        if (Employee::where('user_id', $user->id)->where('id', '!=', $id)->exists()) {
            return response()->json(['error' => 'Ese usuario ya está vinculado a otro empleado.'], 400);
        }

        $employee->update(['user_id' => $user->id]);
        $user->update(['role' => 'employee']);

        return response()->json(['success' => true, 'user_name' => $user->name, 'user_id' => $user->id]);
    }

    public function unlinkAccount(int $id, Request $request)
    {
        $shop = $this->getOwnerShop($request);
        $employee = Employee::find($id);

        if (!$employee || $employee->shop_id !== $shop->id) {
            return response()->json(['error' => 'Empleado no encontrado.'], 404);
        }
        if (!$employee->user_id) {
            return response()->json(['error' => 'Este empleado no tiene cuenta vinculada.'], 400);
        }

        User::where('id', $employee->user_id)->update(['role' => 'client']);
        $employee->update(['user_id' => null]);

        return response()->json(['success' => true]);
    }

    private function saveEmployeeHours(int $employeeId, Request $request): void
    {
        $days = $request->input('emp_days', []);
        $open = $request->input('emp_opens', []);
        $close = $request->input('emp_closes', []);

        EmployeeHour::where('employee_id', $employeeId)->delete();

        for ($d = 0; $d <= 6; $d++) {
            if (isset($days[$d]) && !empty($open[$d]) && !empty($close[$d])) {
                EmployeeHour::create([
                    'employee_id' => $employeeId, 'day_of_week' => $d,
                    'opens_at' => $open[$d], 'closes_at' => $close[$d],
                ]);
            }
        }
    }
}
