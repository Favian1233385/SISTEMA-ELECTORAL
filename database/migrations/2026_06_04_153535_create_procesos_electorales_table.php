<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Crear la tabla maestra de procesos
        Schema::create('procesos_electorales', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150); // Ej: "Elecciones Generales 2026"
            $table->year('anio');          // 2026
            $table->enum('estado', ['activo', 'archivado'])->default('activo');
            $table->timestamps();
        });

        // 2. Insertar el proceso inicial por defecto (2026) para no romper los datos actuales
        DB::table('procesos_electorales')->insert([
            'nombre' => 'Elecciones Iniciales 2026',
            'anio' => 2026,
            'estado' => 'activo',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // 3. Modificar la tabla 'partidos' para vincularla al proceso
        Schema::table('partidos', function (Blueprint $table) {
            $table->unsignedBigInteger('proceso_electoral_id')->default(1)->after('id');
            $table->foreign('proceso_electoral_id')
                  ->references('id')
                  ->on('procesos_electorales')
                  ->onDelete('restrict'); 
        });

        // 4. Modificar la tabla 'actas' para vincularla al proceso
        Schema::table('actas', function (Blueprint $table) {
            $table->unsignedBigInteger('proceso_electoral_id')->default(1)->after('id');
            $table->foreign('proceso_electoral_id')
                  ->references('id')
                  ->on('procesos_electoral_id' ? 'procesos_electorales' : 'procesos_electorales')
                  ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('actas', function (Blueprint $table) {
            $table->dropForeign(['proceso_electoral_id']);
            $table->dropColumn('proceso_electoral_id');
        });

        Schema::table('partidos', function (Blueprint $table) {
            $table->dropForeign(['proceso_electoral_id']);
            $table->dropColumn('proceso_electoral_id');
        });

        Schema::dropIfExists('procesos_electorales');
    }
};