<?php

use App\Http\Controllers\Api\CepController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\DocumentationController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ScheduleController;
use App\Models\Schedule;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('throttle:custom')->group(function () {
    // Rotas de autenticação públicas
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle.configurable:auth');

    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle.configurable:auth');

    Route::get('/forgot-password', [AuthController::class, 'showForgotPasswordForm'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])
        ->middleware('throttle.configurable:auth')->name('password.email');

    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPasswordForm'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])
        ->middleware('throttle.configurable:auth')->name('password.update');

    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');
    Route::get('/logout', fn () => redirect()->route('login'));

    // API pública com rate limiting
    Route::middleware(['throttle.configurable:public'])->group(function () {
        Route::get('api/cep/{cep}', [CepController::class, 'show']);
    });
});

// Rotas autenticadas
Route::middleware(['auth', 'throttle:custom'])->group(function () {
    // Rotas para Administradores
    Route::middleware(['role:admin'])->group(function () {
        Route::resource('patients', PatientController::class);
        Route::resource('doctors', DoctorController::class);
        Route::resource('schedules', ScheduleController::class)->except('show');
        Route::get('schedules/{schedule}', function (Schedule $schedule) {
            return redirect()->route('schedules.edit', $schedule);
        })->name('schedules.show');
        Route::patch('schedules/{schedule}/availability', [ScheduleController::class, 'toggleAvailability'])
            ->name('schedules.toggle-availability');
        Route::get('documentation/pdf', [DocumentationController::class, 'generatePDF'])->name('documentation.pdf');
    });

    // Rotas para Médicos
    Route::middleware(['role:medico'])->group(function () {
        Route::get('my-schedules', [ScheduleController::class, 'index'])->name('my-schedules');
    });

    // Rotas para Pacientes e Administradores (gestor agenda em nome do paciente)
    Route::middleware(['role:admin,paciente'])->group(function () {
        Route::get('appointments/create', [AppointmentController::class, 'create'])->name('appointments.create');
        Route::post('appointments', [AppointmentController::class, 'store'])->name('appointments.store');
    });

    // Rotas para Pacientes
    Route::middleware(['role:paciente'])->group(function () {
        Route::get('appointments/available', [AppointmentController::class, 'availableSlots'])->name('appointments.available');
        Route::post('appointments/quick-book', [AppointmentController::class, 'quickBook'])->name('appointments.quick-book');
    });

    // Rotas gerais (acessíveis por todos os usuários autenticados)
    Route::get('appointments', [AppointmentController::class, 'index'])->name('appointments.index');
    Route::get('appointments/{appointment}', [AppointmentController::class, 'show'])->name('appointments.show')->whereNumber('appointment');
    Route::post('appointments/{appointment}/cancel', [AppointmentController::class, 'cancel'])->name('appointments.cancel');
    Route::get('certificates/{appointment}/generate', [CertificateController::class, 'generate'])->name('certificates.generate');
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('api/schedules/available', [ScheduleController::class, 'getAllAvailable'])->name('schedules.available')->middleware('throttle.configurable:api');
    Route::get('api/schedules/status', [ScheduleController::class, 'status'])->name('schedules.status')->middleware('throttle.configurable:api');

    // Rotas de edição de consultas e confirmação (para admin e medico)
    Route::middleware(['role:admin,medico'])->group(function () {
        Route::post('appointments/{appointment}/confirm', [AppointmentController::class, 'confirm'])->name('appointments.confirm');
        Route::get('appointments/{appointment}/edit', [AppointmentController::class, 'edit'])->name('appointments.edit');
        Route::put('appointments/{appointment}', [AppointmentController::class, 'update'])->name('appointments.update');
    });

    // Rotas de exclusão (apenas admin para preservar histórico médico)
    Route::middleware(['role:admin'])->group(function () {
        Route::delete('appointments/{appointment}', [AppointmentController::class, 'destroy'])->name('appointments.destroy');
    });

    // Rotas de perfil (acessíveis por todos os usuários autenticados)
    Route::get('profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile/update', [ProfileController::class, 'update'])->name('profile.update');
});
