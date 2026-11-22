<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Schedule;
use App\Notifications\AppointmentBookedNotification;
use App\Notifications\AppointmentCancelledNotification;
use App\Notifications\AppointmentConfirmedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class AppointmentController extends Controller
{
    private function authorizeAccess(Appointment $appointment): void
    {
        $user = Auth::user();

        if ($user->isAdmin()) {
            return;
        }

        if ($user->isMedico() && $user->doctor
            && (int) $user->doctor->id === (int) $appointment->doctor_id) {
            return;
        }

        if ($user->isPaciente() && $user->patient
            && (int) $user->patient->id === (int) $appointment->patient_id) {
            return;
        }

        abort(403);
    }

    private function safeNotify($notifiable, $notification): void
    {
        try {
            $notifiable?->notify($notification);
        } catch (\Throwable $e) {
            Log::warning('Falha ao enviar notificação de consulta', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Mesma regra de data que o agendamento manual (store): somente após hoje.
     */
    private function bookingDatePassesPolicy($date): bool
    {
        $date = is_string($date) ? \Carbon\Carbon::parse($date) : $date;
        return ! $date->lt(today());
    }

    private function slotHasActiveConflict(int $doctorId, string $date, string $time): bool
    {
        return Appointment::query()
            ->where('doctor_id', $doctorId)
            ->where('date', $date)
            ->where('time', $time)
            ->where('status', '!=', 'cancelada')
            ->exists();
    }

    private function releaseSlot(Appointment $appointment): void
    {
        $appointmentDate = $appointment->date->format('Y-m-d');
        $appointmentTime = \Carbon\Carbon::parse($appointment->time)->format('H:i:s');

        $schedule = Schedule::where('doctor_id', $appointment->doctor_id)
            ->where('date', $appointmentDate)
            ->whereRaw('TIME(available_time) = ?', [$appointmentTime])
            ->first();

        if ($schedule) {
            $schedule->update(['is_available' => true]);
        }
    }

    /** Lista consultas filtradas conforme o perfil do usuário autenticado. */
    public function index()
    {
        $user = Auth::user();

        $query = Appointment::with(['patient.user', 'doctor.user'])
            ->orderBy('date')
            ->orderBy('time');

        if ($user->isMedico() && $user->doctor) {
            $query->where('doctor_id', $user->doctor->id);
        } elseif ($user->isPaciente() && $user->patient) {
            $query->where('patient_id', $user->patient->id);
        }

        $appointments = $query->paginate(15);

        return view('appointments.index', compact('appointments'));
    }

    /** Exibe o formulário de agendamento manual (admin ou paciente). */
    public function create()
    {
        $doctors = Doctor::with('user')->get();
        $patients = Auth::user()->isAdmin()
            ? Patient::with('user')->orderBy('id', 'desc')->get()
            : collect();

        return view('appointments.create', compact('doctors', 'patients'));
    }

    /** Registra consulta em transação com lock no horário, admin agenda em nome de paciente. */
    public function store(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'doctor_id' => ['required', Rule::exists('doctors', 'id')->whereNull('deleted_at')],
            'date' => 'required|date|after_or_equal:today',
            'time' => 'required|date_format:H:i',
            'notes' => 'nullable|string',
            'patient_id' => [$user->isAdmin() ? 'required' : 'nullable', 'exists:patients,id'],
        ]);

        $patient = $user->isAdmin()
            ? Patient::findOrFail($data['patient_id'])
            : $user->patient;

        if (! $patient) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Perfil de paciente não encontrado.'], 404);
            }

            return back()->with('error', 'Perfil de paciente não encontrado.');
        }

        try {
            $appointment = DB::transaction(function () use ($data, $patient) {
                $schedule = Schedule::where('doctor_id', $data['doctor_id'])
                    ->where('date', $data['date'])
                    ->where('available_time', $data['time'])
                    ->where('is_available', true)
                    ->lockForUpdate()
                    ->first();

                if (! $schedule) {
                    throw new \Exception('Horário não disponível.');
                }

                if ($this->slotHasActiveConflict($data['doctor_id'], $data['date'], $data['time'])) {
                    throw new \Exception('Horário já ocupado.');
                }

                if (\Carbon\Carbon::parse($data['date'])->gt(now()->addDays(7))) {
                    throw new \Exception('Não é possível agendar com mais de 7 dias de antecedência.');
                }

                $slotDateTime = \Carbon\Carbon::parse($data['date'].' '.$data['time']);
                if ($slotDateTime->lte(now())) {
                    throw new \Exception('Este horário já passou.');
                }

                $appointment = Appointment::create([
                    'patient_id' => $patient->id,
                    'doctor_id' => $data['doctor_id'],
                    'date' => $data['date'],
                    'time' => $data['time'],
                    'status' => 'pendente',
                    'notes' => $data['notes'] ?? null,
                ]);

                $schedule->update(['is_available' => false]);

                return $appointment;
            });
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
            }

            return back()->with('error', $e->getMessage());
        }

        $this->safeNotify($patient->user, new AppointmentBookedNotification($appointment));

        if ($request->expectsJson()) {
            session()->flash('success', 'Consulta agendada com sucesso!');

            return response()->json(['success' => true, 'message' => 'Consulta agendada com sucesso!', 'redirect' => route('appointments.index')]);
        }

        return redirect()->route('appointments.index')->with('success', 'Consulta agendada com sucesso!');
    }

    /** Exibe detalhes de uma consulta acessível ao usuário. */
    public function show(Appointment $appointment)
    {
        $this->authorizeAccess($appointment);
        $appointment->load(['patient.user', 'doctor.user']);

        return view('appointments.show', compact('appointment'));
    }

    /** Exibe formulário de edição de consulta (somente anotações). */
    public function edit(Appointment $appointment)
    {
        $this->authorizeAccess($appointment);
        $appointment->load(['patient.user', 'doctor.user']);

        return view('appointments.edit', compact('appointment'));
    }

    /** Atualiza anotações da consulta, consultas canceladas não podem ser editadas. */
    public function update(Request $request, Appointment $appointment)
    {
        $this->authorizeAccess($appointment);

        if ($appointment->status === 'cancelada') {
            return back()->with('error', 'Não é possível editar consultas canceladas.');
        }

        $data = $request->validate([
            'notes' => 'nullable|string',
        ]);

        $appointment->update($data);

        return redirect()->route('appointments.show', $appointment)->with('success', 'Consulta atualizada com sucesso!');
    }

    /** Confirma consulta pendente, permitido ao admin ou ao médico responsável. */
    public function confirm(Appointment $appointment)
    {
        $user = Auth::user();

        $canConfirm = $user->isAdmin()
            || ($user->isMedico() && $user->doctor && (int) $user->doctor->id === (int) $appointment->doctor_id);

        if (! $canConfirm) {
            abort(403);
        }

        if ($appointment->status === 'confirmada') {
            return back()->with('success', 'Esta consulta já está confirmada.');
        }

        if ($appointment->status !== 'pendente') {
            return back()->with('error', 'Apenas consultas pendentes podem ser confirmadas.');
        }

        $slotDateTime = \Carbon\Carbon::parse(
            $appointment->date->format('Y-m-d').' '.\Carbon\Carbon::parse($appointment->time)->format('H:i:s')
        );
        if ($slotDateTime->lte(now())) {
            return back()->with('error', 'Não é possível confirmar consulta com horário já passado.');
        }

        $appointment->update(['status' => 'confirmada']);

        $this->safeNotify($appointment->patient?->user, new AppointmentConfirmedNotification($appointment));

        return back()->with('success', 'Consulta confirmada!');
    }

    /** Cancela consulta e libera o horário, paciente exige mais de 30 minutos de antecedência. */
    public function cancel(Appointment $appointment)
    {
        $this->authorizeAccess($appointment);

        if ($appointment->status === 'cancelada') {
            return back()->with('error', 'Esta consulta já está cancelada.');
        }

        if (Auth::user()->isPaciente()) {
            $slotDateTime = \Carbon\Carbon::parse(
                $appointment->date->format('Y-m-d').' '.\Carbon\Carbon::parse($appointment->time)->format('H:i:s')
            );
            if ($slotDateTime->lte(now()->addMinutes(30))) {
                return back()->with('error', 'Não é possível cancelar com menos de 30 minutos de antecedência.');
            }
        }

        DB::transaction(function () use ($appointment) {
            $appointment->update(['status' => 'cancelada']);
            $this->releaseSlot($appointment);
        });

        $this->safeNotify($appointment->patient?->user, new AppointmentCancelledNotification($appointment));

        return back()->with('success', 'Consulta cancelada!');
    }

    /** Remove consulta logicamente (soft delete) e libera o horário, exclusivo do admin. */
    public function destroy(Appointment $appointment)
    {
        $this->authorizeAccess($appointment);

        DB::transaction(function () use ($appointment) {
            if ($appointment->status !== 'cancelada') {
                $this->releaseSlot($appointment);
            }

            $appointment->delete();
        });

        return redirect()->route('appointments.index')->with('success', 'Consulta removida!');
    }

    /** Lista horários disponíveis para agendamento rápido do paciente (próximos 7 dias). */
    public function availableSlots()
    {
        $availableSlots = Schedule::with(['doctor.user'])
            ->where('is_available', true)
            ->whereHas('doctor', function ($q) {
                $q->whereNull('doctors.deleted_at');
            })
            ->where(function ($q) {
                $q->where('date', '>', now()->toDateString())
                    ->orWhere(function ($q2) {
                        $q2->where('date', now()->toDateString())
                            ->where('available_time', '>', now()->format('H:i:s'));
                    });
            })
            ->where('date', '<=', now()->addDays(7)->toDateString())
            ->orderBy('date')
            ->orderBy('available_time')
            ->get()
            ->groupBy('date');

        return view('appointments.available', compact('availableSlots'));
    }

    /** Agendamento rápido: reserva slot livre em transação com lock e notifica o paciente. */
    public function quickBook(Request $request)
    {
        $data = $request->validate([
            'schedule_id' => 'required|exists:schedules,id',
        ]);

        $user = Auth::user();
        $patient = $user->patient;

        if (! $patient) {
            return back()->with('error', 'Perfil de paciente não encontrado.');
        }

        try {
            $appointment = DB::transaction(function () use ($data, $patient) {
                $schedule = Schedule::lockForUpdate()->find($data['schedule_id']);

                if (! $schedule || ! $schedule->is_available) {
                    throw new \Exception('Horário não está mais disponível.');
                }

                if (! $schedule->doctor || $schedule->doctor->trashed()) {
                    throw new \Exception('Médico não está mais disponível.');
                }

                if (! $this->bookingDatePassesPolicy($schedule->date)) {
                    throw new \Exception('Não é possível agendar para esta data.');
                }

                $scheduleDate = is_string($schedule->date) ? \Carbon\Carbon::parse($schedule->date) : $schedule->date;
                if ($scheduleDate->gt(now()->addDays(7))) {
                    throw new \Exception('Não é possível agendar com mais de 7 dias de antecedência.');
                }

                $slotDateTime = \Carbon\Carbon::parse(
                    $scheduleDate->toDateString().' '.$schedule->available_time->format('H:i')
                );
                if ($slotDateTime->lte(now())) {
                    throw new \Exception('Este horário já passou.');
                }

                if ($this->slotHasActiveConflict($schedule->doctor_id, $schedule->date, $schedule->available_time)) {
                    throw new \Exception('Horário já ocupado.');
                }

                $appointment = Appointment::create([
                    'patient_id' => $patient->id,
                    'doctor_id' => $schedule->doctor_id,
                    'date' => $schedule->date,
                    'time' => $schedule->available_time,
                    'status' => 'pendente',
                ]);

                $schedule->update(['is_available' => false]);

                return $appointment;
            });
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->safeNotify($patient->user, new AppointmentBookedNotification($appointment));

        return redirect()->route('appointments.index')->with('success', 'Consulta agendada com sucesso!');
    }
}
