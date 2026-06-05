<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Agregar la columna 'tipo' a la tabla maestra
        Schema::table('procesos_electorales', function (Blueprint $table) {
            $table->enum('tipo', ['generales', 'primarias'])
                  ->default('generales')
                  ->after('anio');
        });

        // 2. Resguardar la integridad del registro inicial (ID 1)
        // Aseguramos que el proceso por defecto quede explícitamente como generales
        DB::table('procesos_electorales')
            ->where('id', 1)
            ->update(['tipo' => 'generales']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('procesos_electorales', function (Blueprint $table) {
            $table->dropColumn('tipo');
        });
    }
};