<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Schedule;
use App\Models\User;
use App\Notifications\AppointmentBookedNotification;
use App\Notifications\AppointmentCancelledNotification;
use App\Notifications\AppointmentConfirmedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AppointmentNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function makePatient(): array
    {
        $user = User::factory()->create(['role' => 'paciente', 'password' => 'password']);
        $patient = Patient::create([
            'user_id' => $user->id, 'cpf' => '111.111.111-11', 'birth_date' => '1990-01-01',
            'phone' => '(11) 99999-9999',
        ]);

        return [$user, $patient];
    }

    private function makeDoctor(): array
    {
        $user = User::factory()->create(['role' => 'medico']);
        $doctor = Doctor::create([
            'user_id' => $user->id, 'crm' => 'CRM-TEST-1', 'specialty' => 'Clínica Geral',
        ]);

        return [$user, $doctor];
    }

    private function makeSchedule(int $doctorId, string $date, string $time): Schedule
    {
        // Insert raw: o cast 'date' do model gravaria 'Y-m-d H:i:s' e o sqlite
        // (testes) compara strings, diferente do MySQL que coage DATE.
        $id = DB::table('schedules')->insertGetId([
            'doctor_id' => $doctorId, 'date' => $date, 'available_time' => $time,
            'is_available' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return Schedule::findOrFail($id);
    }

    public function test_all_appointment_notifications_fire(): void
    {
        Notification::fake();

        [$patientUser, $patient] = $this->makePatient();
        [$medicoUser, $doctor] = $this->makeDoctor();
        $admin = User::factory()->create(['role' => 'admin']);

        $date = now()->addDays(2)->toDateString();

        // 1) Paciente agenda via quickBook
        $schedule = $this->makeSchedule($doctor->id, $date, '14:00');
        $this->actingAs($patientUser)->post('/appointments/quick-book', ['schedule_id' => $schedule->id])
            ->assertRedirect(route('appointments.index'));

        Notification::assertSentTo($patientUser, AppointmentBookedNotification::class, function ($n) use ($patientUser, $doctor) {
            return $n->appointment->patient_id === $patientUser->patient->id
                && $n->appointment->doctor_id === $doctor->id
                && $n->appointment->date->format('Y-m-d') === now()->addDays(2)->toDateString();
        });

        // 2) Admin agenda via store (outro slot)
        $date2 = now()->addDays(3)->toDateString();
        $this->makeSchedule($doctor->id, $date2, '10:00');
        $this->actingAs($admin)->post('/appointments', [
            'doctor_id' => $doctor->id, 'date' => $date2, 'time' => '10:00',
            'patient_id' => $patient->id, 'notes' => 'teste',
        ])->assertRedirect(route('appointments.index'));

        Notification::assertSentTo($patientUser, AppointmentBookedNotification::class, function ($n) use ($date2) {
            return $n->appointment->date->format('Y-m-d') === $date2
                && \Carbon\Carbon::parse($n->appointment->time)->format('H:i') === '10:00';
        });

        // 3) Medico confirma a consulta do cenario 2
        $appt = Appointment::whereDate('date', $date2)->firstOrFail();
        $this->actingAs($medicoUser)->post("/appointments/{$appt->id}/confirm")
            ->assertSessionHas('success');

        Notification::assertSentTo($patientUser, AppointmentConfirmedNotification::class, function ($n) use ($appt) {
            return $n->appointment->id === $appt->id;
        });

        // 4) Paciente cancela -> AppointmentCancelledNotification
        $this->actingAs($patientUser)->post("/appointments/{$appt->id}/cancel")
            ->assertSessionHas('success');

        Notification::assertSentTo($patientUser, AppointmentCancelledNotification::class, function ($n) use ($appt) {
            return $n->appointment->id === $appt->id;
        });
    }
}
