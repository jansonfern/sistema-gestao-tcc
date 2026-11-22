<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Roberto Almeida',
                'email' => 'admin@clinica.com',
                'password' => 'senha123',
                'role' => 'admin',
            ],
            [
                'name' => 'Dr. João Silva',
                'email' => 'joao.silva@clinica.com',
                'password' => 'senha123',
                'role' => 'medico',
            ],
            [
                'name' => 'Dra. Maria Santos',
                'email' => 'maria.santos@clinica.com',
                'password' => 'senha123',
                'role' => 'medico',
            ],
            [
                'name' => 'Carlos Oliveira',
                'email' => 'carlos@email.com',
                'password' => 'senha123',
                'role' => 'paciente',
            ],
            [
                'name' => 'Ana Costa',
                'email' => 'ana@email.com',
                'password' => 'senha123',
                'role' => 'paciente',
            ],
        ];

        foreach ($users as $userData) {
            $user = \App\Models\User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => \Illuminate\Support\Facades\Hash::make($userData['password']),
                'role' => $userData['role'],
            ]);

            $this->command->info("Usuário criado: {$user->name} ({$user->email}) - {$user->role}");

            // Criar perfil para pacientes
            if ($userData['role'] === 'paciente') {
                \App\Models\Patient::create([
                    'user_id' => $user->id,
                    'cpf' => '12345678901'.str_pad($user->id, 2, '0'),
                    'birth_date' => '1990-01-01',
                    'phone' => '(11) 99999-9999',
                ]);
                $this->command->info('  -> Perfil de paciente criado');
            }

            // Criar perfil para médicos
            if ($userData['role'] === 'medico') {
                \App\Models\Doctor::create([
                    'user_id' => $user->id,
                    'crm' => 'CRM'.str_pad($user->id, 5, '0'),
                    'specialty' => $user->name === 'Dr. João Silva' ? 'Cardiologia' : 'Pediatria',
                ]);
                $this->command->info('  -> Perfil de médico criado');
            }
        }
    }
}
