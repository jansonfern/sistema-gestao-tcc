<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $patients_count = \App\Models\Patient::count();
        $doctors_count = \App\Models\Doctor::count();
        $schedules_count = \App\Models\Schedule::count();
        $appointments_today_count = Appointment::where('date', now()->toDateString())->count();

        $totalAppointments = Appointment::count();
        $pendingAppointments = Appointment::where('status', 'pendente')->count();
        $confirmedAppointments = Appointment::where('status', 'confirmada')->count();
        $cancelledAppointments = Appointment::where('status', 'cancelada')->count();

        $appointmentsBySpecialty = Appointment::join('doctors', 'appointments.doctor_id', '=', 'doctors.id')
            ->selectRaw('doctors.specialty, COUNT(*) as count')
            ->groupBy('doctors.specialty')
            ->get();

        $specialtyLabels = $appointmentsBySpecialty->pluck('specialty');
        $specialtyValues = $appointmentsBySpecialty->pluck('count');

        $driver = DB::connection()->getDriverName();
        $year = (int) date('Y');

        if ($driver === 'sqlite') {
            $appointmentsByMonth = Appointment::query()
                ->selectRaw("CAST(strftime('%m', date) AS INTEGER) as month, COUNT(*) as count")
                ->whereRaw("strftime('%Y', date) = ?", [(string) $year])
                ->groupBy('month')
                ->orderBy('month')
                ->get();
        } else {
            $appointmentsByMonth = Appointment::query()
                ->selectRaw('MONTH(date) as month, COUNT(*) as count')
                ->whereYear('date', $year)
                ->groupBy('month')
                ->orderBy('month')
                ->get();
        }

        $monthLabels = $appointmentsByMonth->pluck('month')->map(function ($month) {
            return date('F', mktime(0, 0, 0, $month, 1));
        });
        $monthValues = $appointmentsByMonth->pluck('count');

        $user = auth()->user();
        $patientAppointments = collect();

        if ($user->role === 'paciente' && $user->patient) {
            $patientAppointments = \App\Models\Appointment::with(['doctor.user'])
                ->where('patient_id', $user->patient->id)
                ->orderByDesc('date')
                ->orderByDesc('time')
                ->get();
        }

        if ($user->role === 'medico' && $user->doctor) {
            $totalConfirmed = Appointment::where('doctor_id', $user->doctor->id)->where('status', 'confirmada')->count();
            $totalPending = Appointment::where('doctor_id', $user->doctor->id)->where('status', 'pendente')->count();
            $totalCancelled = Appointment::where('doctor_id', $user->doctor->id)->where('status', 'cancelada')->count();

            $months = collect(range(1, 12))->map(function ($m) {
                return date('F', mktime(0, 0, 0, $m, 1));
            });

            $confirmedPerMonth = collect();
            $pendingPerMonth = collect();
            $cancelledPerMonth = collect();
            foreach (range(1, 12) as $month) {
                $confirmedPerMonth->push(Appointment::where('doctor_id', $user->doctor->id)
                    ->where('status', 'confirmada')
                    ->whereYear('date', date('Y'))
                    ->whereMonth('date', $month)
                    ->count());
                $pendingPerMonth->push(Appointment::where('doctor_id', $user->doctor->id)
                    ->where('status', 'pendente')
                    ->whereYear('date', date('Y'))
                    ->whereMonth('date', $month)
                    ->count());
                $cancelledPerMonth->push(Appointment::where('doctor_id', $user->doctor->id)
                    ->where('status', 'cancelada')
                    ->whereYear('date', date('Y'))
                    ->whereMonth('date', $month)
                    ->count());
            }

            $specialtyLabels = collect();
            $specialtyValues = collect();

            $monthLabels = collect(range(1, 12))->map(fn ($month) => \Carbon\Carbon::create(null, $month, 1)->locale('pt_BR')->translatedFormat('M'));
            $monthValues = collect(range(1, 12))->map(fn ($month) => $confirmedPerMonth[$month - 1] + $pendingPerMonth[$month - 1] + $cancelledPerMonth[$month - 1]);
            $months = $monthLabels;

            $totalAppointments = $totalConfirmed + $totalPending + $totalCancelled;
            $pendingAppointments = $totalPending;
            $confirmedAppointments = $totalConfirmed;
            $cancelledAppointments = $totalCancelled;
        }

        if ($user->role === 'paciente' && $user->patient) {
            $totalAppointments = Appointment::where('patient_id', $user->patient->id)->count();
            $pendingAppointments = Appointment::where('patient_id', $user->patient->id)->where('status', 'pendente')->count();
            $confirmedAppointments = Appointment::where('patient_id', $user->patient->id)->where('status', 'confirmada')->count();
            $cancelledAppointments = Appointment::where('patient_id', $user->patient->id)->where('status', 'cancelada')->count();
        }

        if (! isset($months)) {
            $months = collect(range(1, 12))->map(function ($m) {
                return ['number' => $m, 'label' => \Carbon\Carbon::create(null, $m, 1)->locale('pt_BR')->translatedFormat('M')];
            });
        }
        if (! isset($confirmedPerMonth)) {
            $confirmedPerMonth = collect(array_fill(0, 12, 0));
        }
        if (! isset($pendingPerMonth)) {
            $pendingPerMonth = collect(array_fill(0, 12, 0));
        }
        if (! isset($cancelledPerMonth)) {
            $cancelledPerMonth = collect(array_fill(0, 12, 0));
        }

        if ($user->role === 'admin') {
            $monthlyTotals = Appointment::query()
                ->selectRaw('MONTH(date) as month, COUNT(*) as count')
                ->whereYear('date', $year)
                ->groupBy('month')
                ->pluck('count', 'month');

            $confirmedMonthlyTotals = Appointment::query()
                ->selectRaw('MONTH(date) as month, COUNT(*) as count')
                ->whereYear('date', $year)
                ->where('status', 'confirmada')
                ->groupBy('month')
                ->pluck('count', 'month');

            $pendingMonthlyTotals = Appointment::query()
                ->selectRaw('MONTH(date) as month, COUNT(*) as count')
                ->whereYear('date', $year)
                ->where('status', 'pendente')
                ->groupBy('month')
                ->pluck('count', 'month');

            $cancelledMonthlyTotals = Appointment::query()
                ->selectRaw('MONTH(date) as month, COUNT(*) as count')
                ->whereYear('date', $year)
                ->where('status', 'cancelada')
                ->groupBy('month')
                ->pluck('count', 'month');

            $monthLabels = collect(range(1, 12))->map(fn ($month) => \Carbon\Carbon::create(null, $month, 1)->locale('pt_BR')->translatedFormat('M'));
            $monthValues = collect(range(1, 12))->map(fn ($month) => $monthlyTotals[$month] ?? 0);
            $confirmedPerMonth = collect(range(1, 12))->map(fn ($month) => $confirmedMonthlyTotals[$month] ?? 0);
            $pendingPerMonth = collect(range(1, 12))->map(fn ($month) => $pendingMonthlyTotals[$month] ?? 0);
            $cancelledPerMonth = collect(range(1, 12))->map(fn ($month) => $cancelledMonthlyTotals[$month] ?? 0);
            $months = $monthLabels;
        }

        return view('dashboard', compact(
            'totalAppointments',
            'pendingAppointments',
            'confirmedAppointments',
            'cancelledAppointments',
            'specialtyLabels',
            'specialtyValues',
            'months',
            'monthLabels',
            'monthValues',
            'confirmedPerMonth',
            'pendingPerMonth',
            'cancelledPerMonth',
            'patientAppointments',
            'patients_count',
            'doctors_count',
            'schedules_count',
            'appointments_today_count'
        ));
    }
}
