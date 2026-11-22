<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $joao = \App\Models\Doctor::whereHas('user', function ($q) {
            $q->where('email', 'joao.silva@clinica.com');
        })->first();
        $maria = \App\Models\Doctor::whereHas('user', function ($q) {
            $q->where('email', 'maria.santos@clinica.com');
        })->first();

        if ($joao && $maria) {
            // Horários para os próximos 7 dias
            $dates = [
                now()->addDays(1)->toDateString(),
                now()->addDays(2)->toDateString(),
                now()->addDays(3)->toDateString(),
                now()->addDays(4)->toDateString(),
                now()->addDays(5)->toDateString(),
                now()->addDays(6)->toDateString(),
                now()->addDays(7)->toDateString(),
            ];

            $times = ['09:00', '10:00', '14:00', '15:00', '16:00'];

            foreach ($dates as $date) {
                foreach ($times as $time) {
                    \App\Models\Schedule::create([
                        'doctor_id' => $joao->id,
                        'date' => $date,
                        'available_time' => $time,
                        'is_available' => true,
                    ]);
                }
            }

            $this->command->info('Horários criados para Dr. João Silva: '.(count($dates) * count($times)));

            foreach ($dates as $date) {
                foreach ($times as $time) {
                    \App\Models\Schedule::create([
                        'doctor_id' => $maria->id,
                        'date' => $date,
                        'available_time' => $time,
                        'is_available' => true,
                    ]);
                }
            }

            $this->command->info('Horários criados para Dra. Maria Santos: '.(count($dates) * count($times)));
            $this->command->info('Total de horários: '.\App\Models\Schedule::count());
            $this->command->info('Data inicial: '.$dates[0]);
            $this->command->info('Data final: '.end($dates));
        } else {
            $this->command->error('Erro: Médicos não encontrados no banco de dados.');
        }
    }
}
