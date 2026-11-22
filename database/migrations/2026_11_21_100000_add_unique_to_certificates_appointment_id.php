<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->unique('appointment_id', 'certificates_appointment_id_unique');
        });
    }

    public function down(): void
    {
        // O MySQL pode ter descartado o índice implícito da FK ao criar o UNIQUE;
        // por isso a FK é removida e recriada para o dropUnique não falhar.
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropForeign(['appointment_id']);
            $table->dropUnique('certificates_appointment_id_unique');
            $table->foreign('appointment_id')->references('id')->on('appointments')->restrictOnDelete();
        });
    }
};
