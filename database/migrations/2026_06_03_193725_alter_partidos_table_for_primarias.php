<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('partidos', function (Blueprint $table) {
            // 1. Eliminamos el índice único que bloquea los nombres repetidos
            $table->dropUnique(['nombre']);
            
            // 2. Añadimos la columna del proceso con 'generales' por defecto para proteger tus datos reales
            $table->string('proceso_eleccion', 20)->default('generales')->after('logo');
            
            // 3. Opcional: Si requieres indexar para mejorar velocidad de búsquedas por proceso
            $table->index('proceso_eleccion');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('partidos', function (Blueprint $table) {
            // Operación inversa en caso de rollback
            $table->dropIndex(['proceso_eleccion']);
            $table->dropColumn('proceso_eleccion');
            $table->string('nombre', 150)->unique()->change();
        });
    }
};