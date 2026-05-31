<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Aislamos las mesas por proceso
        Schema::table('mesas', function (Blueprint $table) {
            $table->string('tipo_proceso')->default('general')->after('num_electores'); 
            // 'general' o 'primaria'
        });

        // Aislamos los candidatos por proceso
        Schema::table('candidatos', function (Blueprint $table) {
            $table->string('tipo_proceso')->default('general')->after('dignidad');
        });

        // Identificamos el ámbito de operación de cada usuario
        Schema::table('users', function (Blueprint $table) {
            $table->string('tipo_proceso')->default('general')->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('mesas', function (Blueprint $table) { $table->dropColumn('tipo_proceso'); });
        Schema::table('candidatos', function (Blueprint $table) { $table->dropColumn('tipo_proceso'); });
        Schema::table('users', function (Blueprint $table) { $table->dropColumn('tipo_proceso'); });
    }
};