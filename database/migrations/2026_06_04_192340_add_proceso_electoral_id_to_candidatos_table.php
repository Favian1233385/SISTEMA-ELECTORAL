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
        Schema::table('candidatos', function (Blueprint $table) {
            // 1. Añadimos la columna como un entero sin signo que acepta nulos (por seguridad inicial)
            // Se coloca después del id de partidos si existe, o simplemente en la tabla.
            $table->unsignedBigInteger('proceso_electoral_id')->nullable()->after('partido_id');

            // 2. Creamos la relación de llave foránea apuntando a tu tabla de procesos
            $table->foreign('proceso_electoral_id')
                  ->references('id')
                  ->on('procesos_electorales')
                  ->onDelete('cascade'); // Si se borra un proceso, se limpian sus candidatos
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('candidatos', function (Blueprint $table) {
            // Eliminamos la llave foránea y la columna si se hace un rollback
            $table->dropForeign(['proceso_electoral_id']);
            $table->dropColumn('proceso_electoral_id');
        });
    }
};