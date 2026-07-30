<?php

use App\Http\Controllers\{
    HomeController, ShopController, BookingController, ReportController, ReviewController,
    AuthController, ClientController, ShopDashController, ShopSettingsController,
    EmployeeController, ServiceController, StatsController, AdminController,
    AccountController, EmployeeDashController, ShopRegisterController
};
use Illuminate\Support\Facades\Route;

// ── Público ──────────────────────────────────────────────────────────
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/buscar', [HomeController::class, 'search'])->name('search');
Route::get('/local/{slug}', [ShopController::class, 'show'])->name('shop.show');
Route::get('/local/{slug}/servicios', [ShopController::class, 'services'])->name('shop.services');
Route::get('/local/{slug}/resenas', [ShopController::class, 'reviews'])->name('shop.reviews');
Route::post('/local/{slug}/favorito', [ShopController::class, 'toggleFavorite'])->middleware('auth')->name('shop.favorite');

Route::get('/api/slots', [BookingController::class, 'apiSlots'])->name('api.slots');
Route::get('/api/empleados-para-servicio', [BookingController::class, 'apiEmployeesForService'])->name('api.employeesForService');

Route::post('/local/{slug}/reportar', [ReportController::class, 'store'])->middleware('auth')->name('shop.report');
Route::post('/resena/reportar', [ReviewController::class, 'report'])->middleware('auth')->name('review.report');

Route::get('/reservar/gracias/{token}', [BookingController::class, 'thanks'])->name('booking.thanks');
Route::get('/cancelar/{token}', [BookingController::class, 'cancelByToken'])->name('booking.cancelByToken');

// ── Flujo de reserva (client_only: invitado o cliente, nunca negocio/admin) ──
Route::middleware('client_only')->group(function () {
    Route::get('/reservar/{slug}', [BookingController::class, 'step1'])->name('booking.step1');
    Route::post('/reservar/{slug}/servicio', [BookingController::class, 'setService'])->name('booking.setService');
    Route::get('/reservar/{slug}/empleado', [BookingController::class, 'setEmployee'])->name('booking.employeeForm');
    Route::post('/reservar/{slug}/empleado', [BookingController::class, 'setEmployee'])->name('booking.setEmployee');
    Route::get('/reservar/{slug}/horario', [BookingController::class, 'step3'])->name('booking.step3');
    Route::post('/reservar/{slug}/horario', [BookingController::class, 'setSlot'])->name('booking.setSlot');
    Route::get('/reservar/{slug}/confirmar', [BookingController::class, 'step4'])->name('booking.step4');
    Route::post('/reservar/{slug}/confirmar', [BookingController::class, 'confirm'])->name('booking.confirm');
});

// ── Auth (login/registro propios — NO Breeze, se mantiene lógica de roles/ban) ──
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'loginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/registro', [AuthController::class, 'registerForm'])->name('register');
    Route::post('/registro', [AuthController::class, 'register']);
});
Route::get('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');
Route::get('/verificar/{token}', [AuthController::class, 'verify'])->name('verify');
Route::get('/recuperar', [AuthController::class, 'forgotForm'])->name('password.request');
Route::post('/recuperar', [AuthController::class, 'forgot'])->name('password.email');
Route::get('/reset/{token}', [AuthController::class, 'resetForm'])->name('password.reset');
Route::post('/reset/{token}', [AuthController::class, 'reset'])->name('password.update');

// ── Cliente ──────────────────────────────────────────────────────────
Route::middleware(['auth', 'role:client'])->group(function () {
    Route::get('/mi-cuenta', [ClientController::class, 'dashboard'])->name('client.dashboard');
    Route::post('/mi-cuenta', [ClientController::class, 'profile'])->name('client.profile');
    Route::get('/mis-turnos', [ClientController::class, 'appointments'])->name('client.appointments');
    Route::post('/cancelar-turno/{id}', [ClientController::class, 'cancel'])->name('client.cancel');
    Route::post('/resena/{id}', [ClientController::class, 'review'])->name('client.review');
    Route::get('/mis-favoritos', [ClientController::class, 'favorites'])->name('client.favorites');
});
Route::get('/mis-turnos/seguimiento', [ClientController::class, 'track'])->name('client.track');

// ── Panel del dueño del local ────────────────────────────────────────
Route::middleware(['auth', 'role:shop_owner'])->prefix('panel')->name('panel.')->group(function () {
    Route::get('/', [ShopDashController::class, 'index'])->name('index');
    Route::get('/agenda', [ShopDashController::class, 'agenda'])->name('agenda');
    Route::get('/agenda/api', [ShopDashController::class, 'agendaApi'])->name('agenda.api');
    Route::get('/turnos', [ShopDashController::class, 'appointments'])->name('appointments');
    Route::post('/turnos/{id}/status', [ShopDashController::class, 'updateStatus'])->name('appointments.status');
    Route::post('/notas/{id}/descartar', [ShopDashController::class, 'dismissNote'])->name('notes.dismiss');

    Route::get('/local', [ShopSettingsController::class, 'edit'])->name('settings.edit');
    Route::post('/local', [ShopSettingsController::class, 'update'])->name('settings.update');
    Route::post('/local/horarios', [ShopSettingsController::class, 'saveHours'])->name('settings.hours');
    Route::post('/local/fotos', [ShopSettingsController::class, 'uploadPhoto'])->name('settings.photos.upload');
    Route::post('/local/fotos/{id}/delete', [ShopSettingsController::class, 'deletePhoto'])->name('settings.photos.delete');

    Route::get('/empleados', [EmployeeController::class, 'index'])->name('employees.index');
    Route::get('/empleados/nuevo', [EmployeeController::class, 'create'])->name('employees.create');
    Route::post('/empleados', [EmployeeController::class, 'store'])->name('employees.store');
    Route::get('/empleados/{id}', [EmployeeController::class, 'edit'])->name('employees.edit');
    Route::post('/empleados/{id}', [EmployeeController::class, 'update'])->name('employees.update');
    Route::post('/empleados/{id}/eliminar', [EmployeeController::class, 'delete'])->name('employees.delete');
    Route::post('/empleados/{id}/vincular', [EmployeeController::class, 'linkAccount'])->name('employees.link');
    Route::post('/empleados/{id}/desvincular', [EmployeeController::class, 'unlinkAccount'])->name('employees.unlink');

    Route::get('/servicios', [ServiceController::class, 'index'])->name('services.index');
    Route::get('/servicios/nuevo', [ServiceController::class, 'create'])->name('services.create');
    Route::post('/servicios', [ServiceController::class, 'store'])->name('services.store');
    Route::get('/servicios/{id}', [ServiceController::class, 'edit'])->name('services.edit');
    Route::post('/servicios/{id}', [ServiceController::class, 'update'])->name('services.update');
    Route::post('/servicios/{id}/eliminar', [ServiceController::class, 'delete'])->name('services.delete');

    Route::get('/resenas', [ReviewController::class, 'shopIndex'])->name('reviews.index');
    Route::post('/resenas/{id}/reply', [ReviewController::class, 'reply'])->name('reviews.reply');
    Route::post('/resenas/{id}/toggle', [ReviewController::class, 'toggle'])->name('reviews.toggle');

    Route::get('/estadisticas', [StatsController::class, 'index'])->name('stats');
});

// ── Panel del empleado ───────────────────────────────────────────────
Route::middleware(['auth', 'role:employee'])->prefix('mi-panel')->name('empdash.')->group(function () {
    Route::get('/', [EmployeeDashController::class, 'index'])->name('index');
    Route::get('/turnos', [EmployeeDashController::class, 'appointments'])->name('appointments');
    Route::get('/historial', [EmployeeDashController::class, 'appointments'])->name('history');
    Route::post('/turnos/{id}/status', [EmployeeDashController::class, 'updateStatus'])->name('appointments.status');
    Route::get('/nuevo-turno', [EmployeeDashController::class, 'newAppointment'])->name('appointments.new');
    Route::post('/turnos/nuevo', [EmployeeDashController::class, 'storeAppointment'])->name('appointments.store');
    Route::get('/servicios', [EmployeeDashController::class, 'services'])->name('services');
    Route::post('/servicios', [EmployeeDashController::class, 'updateServices'])->name('services.update');
    Route::get('/perfil', [EmployeeDashController::class, 'profile'])->name('profile');
    Route::post('/perfil', [EmployeeDashController::class, 'updateProfile'])->name('profile.update');
    Route::get('/fotos', [EmployeeDashController::class, 'photos'])->name('photos');
    Route::post('/fotos', [EmployeeDashController::class, 'uploadPhoto'])->name('photos.upload');
    Route::post('/fotos/{id}/eliminar', [EmployeeDashController::class, 'deletePhoto'])->name('photos.delete');
});

// ── Superadmin ───────────────────────────────────────────────────────
Route::middleware(['auth', 'role:superadmin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('index');
    Route::get('/locales', [AdminController::class, 'shops'])->name('shops');
    Route::get('/locales/{id}', [AdminController::class, 'shopDetail'])->name('shops.detail');
    Route::post('/locales/{id}/moderar', [AdminController::class, 'moderateShop'])->name('shops.moderate');
    Route::get('/usuarios', [AdminController::class, 'users'])->name('users');
    Route::post('/usuarios/{id}/status', [AdminController::class, 'userStatus'])->name('users.status');
    Route::get('/estadisticas', [AdminController::class, 'stats'])->name('stats');
    Route::get('/resenas', [AdminController::class, 'reviews'])->name('reviews');
    Route::get('/periodos', [AdminController::class, 'statPeriods'])->name('periods');
    Route::post('/periodos/crear', [AdminController::class, 'createStatPeriod'])->name('periods.create');
    Route::post('/periodos/{id}/eliminar', [AdminController::class, 'deleteStatPeriod'])->name('periods.delete');
    Route::post('/resenas/{id}/moderar', [AdminController::class, 'moderateReview'])->name('reviews.moderate');
});

// ── Cuenta (cambiar contraseña/email, eliminar) ─────────────────────
Route::middleware('auth')->prefix('cuenta')->name('account.')->group(function () {
    Route::get('/contrasena', [AccountController::class, 'changePasswordForm'])->name('password');
    Route::post('/contrasena', [AccountController::class, 'changePassword'])->name('password.update');
    Route::get('/email', [AccountController::class, 'changeEmailForm'])->name('email');
    Route::post('/email', [AccountController::class, 'changeEmail'])->name('email.update');
    Route::get('/eliminar', [AccountController::class, 'deleteAccountForm'])->name('delete');
    Route::post('/eliminar', [AccountController::class, 'deleteAccount'])->name('delete.confirm');
});

// ── Registrar un local nuevo ─────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/registrar-local', [ShopRegisterController::class, 'form'])->name('shop.register.form');
    Route::post('/registrar-local', [ShopRegisterController::class, 'store'])->name('shop.register.store');
});

// ── 404 (catch-all, siempre al final) ────────────────────────────────
Route::fallback(function () {
    return response()->view('errors.404', [], 404);
});
