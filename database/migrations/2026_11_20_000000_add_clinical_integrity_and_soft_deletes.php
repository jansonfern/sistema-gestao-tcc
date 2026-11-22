<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('patients', function (Blueprint $table) {
            $table->softDeletes();
            $table->unique('user_id', 'patients_user_id_unique');
        });

        Schema::table('doctors', function (Blueprint $table) {
            $table->softDeletes();
            $table->unique('user_id', 'doctors_user_id_unique');
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['patient_id']);
            $table->dropForeign(['doctor_id']);
        });

        Schema::table('certificates', function (Blueprint $table) {
            $table->dropForeign(['appointment_id']);
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->foreign('patient_id')->references('id')->on('patients')->restrictOnDelete();
            $table->foreign('doctor_id')->references('id')->on('doctors')->restrictOnDelete();
        });

        Schema::table('certificates', function (Blueprint $table) {
            $table->foreign('appointment_id')->references('id')->on('appointments')->restrictOnDelete();
        });

        // Soft-deleted appointments must not occupy the slot either.
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE appointments DROP INDEX appointments_active_slot_unique');
            DB::statement('ALTER TABLE appointments DROP COLUMN active_slot_key');
            DB::statement("ALTER TABLE appointments ADD COLUMN active_slot_key VARCHAR(80) GENERATED ALWAYS AS (CASE WHEN status = 'cancelada' OR deleted_at IS NOT NULL THEN NULL ELSE CONCAT(doctor_id, '-', date, '-', time) END) STORED");
            DB::statement('ALTER TABLE appointments ADD UNIQUE INDEX appointments_active_slot_unique (active_slot_key)');
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE appointments DROP INDEX appointments_active_slot_unique');
            DB::statement('ALTER TABLE appointments DROP COLUMN active_slot_key');
            DB::statement("ALTER TABLE appointments ADD COLUMN active_slot_key VARCHAR(80) GENERATED ALWAYS AS (CASE WHEN status = 'cancelada' THEN NULL ELSE CONCAT(doctor_id, '-', date, '-', time) END) STORED");
            DB::statement('ALTER TABLE appointments ADD UNIQUE INDEX appointments_active_slot_unique (active_slot_key)');
        }

        Schema::table('certificates', function (Blueprint $table) {
            $table->dropForeign(['appointment_id']);
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['patient_id']);
            $table->dropForeign(['doctor_id']);
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('doctor_id')->references('id')->on('doctors')->onDelete('cascade');
        });

        Schema::table('certificates', function (Blueprint $table) {
            $table->foreign('appointment_id')->references('id')->on('appointments')->onDelete('cascade');
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('doctors', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropUnique('doctors_user_id_unique');
        });

        Schema::table('patients', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropUnique('patients_user_id_unique');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
