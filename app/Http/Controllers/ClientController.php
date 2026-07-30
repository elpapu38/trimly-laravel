<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Review;
use App\Services\ImageUploader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ClientController extends Controller
{
    private function splitAppointments($user)
    {
        $appts = Appointment::forClientEmail($user->email)
            ->with(['shop', 'employee', 'service', 'review'])
            ->orderByDesc('date')->orderByDesc('start_time')->get();

        $now = now();
        $upcoming = $appts->filter(fn ($a) => $a->date->copy()->setTimeFromTimeString($a->start_time) >= $now
            && !in_array($a->status, ['cancelled_client', 'cancelled_shop']))->values();
        $past = $appts->filter(fn ($a) => $a->date->copy()->setTimeFromTimeString($a->start_time) < $now
            || in_array($a->status, ['cancelled_client', 'cancelled_shop', 'completed']))->values();

        return [$upcoming, $past];
    }

    public function dashboard(Request $request)
    {
        [$upcoming, $past] = $this->splitAppointments($request->user());

        return view('client.dashboard', [
            'pageTitle' => 'Mi cuenta', 'userRow' => $request->user(),
            'upcoming' => $upcoming, 'past' => $past,
        ]);
    }

    public function appointments(Request $request)
    {
        [$upcoming, $past] = $this->splitAppointments($request->user());

        return view('client.dashboard', [
            'pageTitle' => 'Mis turnos', 'userRow' => $request->user(),
            'upcoming' => $upcoming, 'past' => $past,
        ]);
    }

    public function cancel(int $id, Request $request)
    {
        $user = $request->user();
        $appointment = Appointment::find($id);

        if (!$appointment || strtolower($appointment->client_email) !== strtolower($user->email)) {
            return redirect('/mis-turnos')->with('error', 'Turno no encontrado.');
        }
        if (in_array($appointment->status, ['cancelled_client', 'cancelled_shop', 'completed'])) {
            return redirect('/mis-turnos')->with('error', 'Este turno ya no puede cancelarse.');
        }

        $apptTime = strtotime("{$appointment->date->format('Y-m-d')} {$appointment->start_time}");
        if ($apptTime - time() < 7200) {
            return redirect('/mis-turnos')->with('error', 'Solo podés cancelar con al menos 2 horas de anticipación.');
        }

        $appointment->update(['status' => 'cancelled_client']);

        return redirect('/mis-turnos')->with('success', 'Turno cancelado correctamente.');
    }

    public function track(Request $request)
    {
        $appointments = collect();
        $email = '';

        if ($request->isMethod('post')) {
            $email = strtolower(trim($request->input('email', '')));
            if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $appointments = Appointment::forClientEmail($email)
                    ->with(['shop', 'employee', 'service'])
                    ->orderByDesc('date')->orderByDesc('start_time')->get();
            } else {
                $request->session()->flash('error', 'Ingresá un email válido.');
            }
        }

        return view('client.track', [
            'pageTitle' => 'Seguimiento de turnos', 'appointments' => $appointments, 'email' => $email,
        ]);
    }

    public function profile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|min:2|max:100',
            'phone' => 'nullable|string|max:30',
            'new_password' => 'nullable|string|min:8',
            'current_password' => 'nullable|string',
            'avatar' => 'nullable|image|max:3072', // 3 MB
        ]);

        $data = ['name' => strip_tags($validated['name']), 'phone' => strip_tags($validated['phone'] ?? '')];

        if (!empty($validated['new_password'])) {
            if (!Hash::check($request->input('current_password', ''), $user->password)) {
                return back()->withErrors(['current_password' => 'Contraseña actual incorrecta.'])->withInput();
            }
            $data['password'] = $validated['new_password'];
        }

        if ($request->hasFile('avatar')) {
            $path = ImageUploader::upload($request->file('avatar'), 'avatars', 3145728);
            if (!$path) {
                return redirect('/mi-cuenta')->with('error', 'La imagen no es válida o supera los 3 MB.');
            }
            $data['avatar'] = $path;
        }

        $user->update($data);

        return redirect('/mi-cuenta')->with('success', 'Perfil actualizado.');
    }

    public function favorites(Request $request)
    {
        $favorites = $request->user()->favorites()->paginate(12);

        return view('client.favorites', ['pageTitle' => 'Mis favoritos', 'favorites' => $favorites]);
    }

    public function review(int $appointmentId, Request $request)
    {
        $user = $request->user();
        $appointment = Appointment::find($appointmentId);

        if (!$appointment || strtolower($appointment->client_email) !== strtolower($user->email)) {
            return response()->json(['error' => 'No autorizado'], 403);
        }
        if ($appointment->status !== 'completed') {
            return response()->json(['error' => 'Solo podés reseñar turnos completados'], 400);
        }
        if (Review::where('appointment_id', $appointmentId)->exists()) {
            return response()->json(['error' => 'Ya dejaste una reseña para este turno'], 400);
        }

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
        ]);

        Review::create([
            'appointment_id' => $appointmentId, 'shop_id' => $appointment->shop_id,
            'client_id' => $appointment->client_id, 'rating' => $validated['rating'],
            'comment' => strip_tags($validated['comment'] ?? ''), 'is_visible' => true,
        ]);

        $appointment->shop->updateRating();

        return response()->json(['success' => true, 'message' => 'Reseña publicada. ¡Gracias!']);
    }
}
