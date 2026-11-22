<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $scheduleIndexExists = collect(Schema::getIndexes('schedules'))->contains('name', 'schedules_slot_unique');
        if (! $scheduleIndexExists) {
            Schema::table('schedules', function (Blueprint $table) {
                $table->unique(['doctor_id', 'date', 'available_time'], 'schedules_slot_unique');
            });
        }

        $appointmentIndexes = collect(Schema::getIndexes('appointments'))->pluck('name');
        Schema::table('appointments', function (Blueprint $table) use ($appointmentIndexes) {
            if (! $appointmentIndexes->contains('appointments_patient_date_index')) {
                $table->index(['patient_id', 'date'], 'appointments_patient_date_index');
            }
            if (! $appointmentIndexes->contains('appointments_status_date_index')) {
                $table->index(['status', 'date'], 'appointments_status_date_index');
            }
            if (! $appointmentIndexes->contains('appointments_doctor_status_date_index')) {
                $table->index(['doctor_id', 'status', 'date'], 'appointments_doctor_status_date_index');
            }
        });

        // Coluna gerada compatível com MariaDB/MySQL: consultas canceladas não ocupam o slot.
        if (DB::connection()->getDriverName() === 'mysql' && ! Schema::hasColumn('appointments', 'active_slot_key')) {
            DB::statement("ALTER TABLE appointments ADD COLUMN active_slot_key VARCHAR(80) GENERATED ALWAYS AS (CASE WHEN status = 'cancelada' THEN NULL ELSE CONCAT(doctor_id, '-', date, '-', time) END) STORED");
            DB::statement('ALTER TABLE appointments ADD UNIQUE INDEX appointments_active_slot_unique (active_slot_key)');
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'mysql' && Schema::hasColumn('appointments', 'active_slot_key')) {
            DB::statement('ALTER TABLE appointments DROP INDEX appointments_active_slot_unique');
            DB::statement('ALTER TABLE appointments DROP COLUMN active_slot_key');
        }

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex('appointments_patient_date_index');
            $table->dropIndex('appointments_status_date_index');
            $table->dropIndex('appointments_doctor_status_date_index');
        });

        Schema::table('schedules', function (Blueprint $table) {
            $table->dropUnique('schedules_slot_unique');
        });
    }
};
