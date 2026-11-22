<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ScheduleController extends Controller
{
    /**
     * Slots que ainda podem acontecer: data futura OU hoje com horário à frente.
     */
    private function futureSlotConstraint(): \Closure
    {
        return function ($query) {
            $query->where(function ($q) {
                $q->where('date', '>', now()->toDateString())
                    ->orWhere(function ($q2) {
                        $q2->where('date', now()->toDateString())
                            ->where('available_time', '>', now()->format('H:i:s'));
                    });
            });
        };
    }

    private function activeDoctorConstraint(): \Closure
    {
        return function ($query) {
            $query->whereHas('doctor', function ($q) {
                $q->whereNull('doctors.deleted_at');
            });
        };
    }

    public function index()
    {
        $user = Auth::user();

        $query = Schedule::with('doctor.user')
            ->where($this->futureSlotConstraint())
            ->where($this->activeDoctorConstraint())
            ->orderBy('date')
            ->orderBy('available_time');

        if ($user->isAdmin()) {
            $schedules = $query->paginate(15);
        } else {
            $schedules = $query->where('doctor_id', $user->doctor->id)->paginate(15);
        }

        return view('schedules.index', compact('schedules'));
    }

    public function status()
    {
        $user = Auth::user();

        $query = Schedule::with('doctor.user')
            ->where($this->futureSlotConstraint())
            ->where($this->activeDoctorConstraint())
            ->orderBy('date')
            ->orderBy('available_time');

        if (! $user->isAdmin()) {
            $query->where('doctor_id', $user->doctor->id);
        }

        $schedules = $query->get()->map(function ($schedule) use ($user) {
            return [
                'id' => $schedule->id,
                'is_available' => (bool) $schedule->is_available,
                'date' => $schedule->date->format('d/m/Y'),
                'time' => $schedule->available_time->format('H:i'),
                'doctor' => $schedule->doctor->user->name.' - '.$schedule->doctor->specialty,
                'edit_url' => $user->isAdmin() ? route('schedules.edit', $schedule) : null,
                'delete_url' => $user->isAdmin() ? route('schedules.destroy', $schedule) : null,
            ];
        });

        return response()->json($schedules);
    }

    public function create()
    {
        $doctors = Doctor::with('user')->get();

        return view('schedules.create', compact('doctors'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'doctor_id' => ['required', Rule::exists('doctors', 'id')->whereNull('deleted_at')],
            'date' => 'required|date|after_or_equal:today',
            'available_time' => 'required|date_format:H:i',
        ]);

        $existingSchedule = Schedule::where('doctor_id', $data['doctor_id'])
            ->where('date', $data['date'])
            ->where('available_time', $data['available_time'])
            ->first();

        if ($existingSchedule) {
            return back()->withErrors(['available_time' => 'Horário já cadastrado para esta data.']);
        }

        if ($data['date'] === now()->toDateString() && $data['available_time'] <= now()->format('H:i')) {
            return back()->withErrors(['available_time' => 'Não é possível cadastrar um horário que já passou.']);
        }

        Schedule::create([
            'doctor_id' => $data['doctor_id'],
            'date' => $data['date'],
            'available_time' => $data['available_time'],
            'is_available' => true,
        ]);

        return redirect()->route('schedules.index')->with('success', 'Horário cadastrado com sucesso!');
    }

    public function edit(Schedule $schedule)
    {
        $doctors = Doctor::with('user')->get();

        return view('schedules.edit', compact('schedule', 'doctors'));
    }

    public function update(Request $request, Schedule $schedule)
    {
        $data = $request->validate([
            'doctor_id' => ['required', Rule::exists('doctors', 'id')->whereNull('deleted_at')],
            'date' => 'required|date|after_or_equal:today',
            'available_time' => 'required|date_format:H:i',
            'is_available' => 'boolean',
        ]);

        $slotChanged = $schedule->doctor_id !== (int) $data['doctor_id']
            || $schedule->date->toDateString() !== $data['date']
            || $schedule->available_time->format('H:i') !== $data['available_time'];

        if ($data['date'] === now()->toDateString() && $data['available_time'] <= now()->format('H:i')) {
            return back()->withErrors(['available_time' => 'Não é possível definir um horário que já passou.']);
        }

        if ($slotChanged) {
            $conflict = Schedule::where('doctor_id', $data['doctor_id'])
                ->where('date', $data['date'])
                ->where('available_time', $data['available_time'])
                ->whereKeyNot($schedule->id)
                ->exists();

            if ($conflict) {
                return back()->withErrors(['available_time' => 'Já existe uma agenda para este médico, data e horário.']);
            }

            $newTime = \Carbon\Carbon::parse($data['available_time'])->format('H:i:s');
            $hasAppointment = Appointment::where('doctor_id', $data['doctor_id'])
                ->where('date', $data['date'])
                ->where('time', $newTime)
                ->where('status', '!=', 'cancelada')
                ->exists();

            $data['is_available'] = ! $hasAppointment;
        }

        $schedule->update($data);

        return redirect()->route('schedules.index')->with('success', 'Horário atualizado com sucesso!');
    }

    public function toggleAvailability(Schedule $schedule)
    {
        if (! $schedule->is_available && $schedule->hasActiveAppointment()) {
            return back()->with('error', 'Não é possível liberar um horário com consulta ativa.');
        }

        $schedule->update(['is_available' => ! $schedule->is_available]);

        $status = $schedule->is_available ? 'disponível' : 'ocupado';

        return back()->with('success', "Horário marcado como {$status}.");
    }

    public function destroy(Schedule $schedule)
    {
        DB::transaction(function () use ($schedule) {
            Appointment::where('doctor_id', $schedule->doctor_id)
                ->where('date', $schedule->date->format('Y-m-d'))
                ->where('time', $schedule->available_time->format('H:i:s'))
                ->where('status', '!=', 'cancelada')
                ->update(['status' => 'cancelada']);

            $schedule->delete();
        });

        return redirect()->route('schedules.index')->with('success', 'Horário removido com sucesso!');
    }

    public function getAllAvailable()
    {
        $schedules = Schedule::with('doctor.user')
            ->where('is_available', true)
            ->where($this->futureSlotConstraint())
            ->where($this->activeDoctorConstraint())
            ->where('date', '<=', now()->addDays(7)->toDateString())
            ->orderBy('date')
            ->orderBy('available_time')
            ->get()
            ->map(function ($schedule) {
                $date = is_string($schedule->date) ? $schedule->date : $schedule->date->format('Y-m-d');

                $time = is_string($schedule->available_time)
                    ? substr($schedule->available_time, 0, 5)
                    : $schedule->available_time->format('H:i');

                return [
                    'id' => $schedule->id,
                    'doctor_id' => $schedule->doctor_id,
                    'doctor' => [
                        'id' => $schedule->doctor->id,
                        'user' => [
                            'name' => $schedule->doctor->user->name,
                        ],
                        'specialty' => $schedule->doctor->specialty,
                    ],
                    'date' => $date,
                    'available_time' => $time,
                ];
            });

        return response()->json($schedules);
    }
}
