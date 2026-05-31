<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('mesas', function (Blueprint $table) {
            // Añadimos la columna. Por defecto será 'generales' para heredar el estado de los datos que ya tienes guardados.
            $table->string('proceso_eleccion')->default('generales')->after('genero');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mesas', function (Blueprint $table) {
            // Si necesitas revertir la migración, eliminamos la columna de forma segura
            $table->dropColumn('proceso_eleccion');
        });
    }
};