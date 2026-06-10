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
        // Índices en la tabla de Usuarios (Digitadores)
        Schema::table('users', function (Blueprint $table) {
            // Índice simple para el rol, agiliza la discriminación inicial
            $table->index('role');

            // Índices simples para búsquedas directas por jerarquía territorial
            $table->index('provincia_id');
            $table->index('canton_id');
            $table->index('parroquia_id');

            // Índice compuesto: Optimiza la vista filtrada por territorio y dignidad a la vez
            $table->index(['parroquia_id', 'dignidad_asignada'], 'idx_users_parroquia_dignidad');
            $table->index(['canton_id', 'dignidad_asignada'], 'idx_users_canton_dignidad');
        });

        // Índices en la tabla de Mesas
        Schema::table('mesas', function (Blueprint $table) {
            // Agiliza la segmentación por tipo de elección (primarias/generales)
            $table->index('proceso_eleccion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropIndex(['provincia_id']);
            $table->dropIndex(['canton_id']);
            $table->dropIndex(['parroquia_id']);
            $table->dropIndex('idx_users_parroquia_dignidad');
            $table->dropIndex('idx_users_canton_dignidad');
        });

        Schema::table('mesas', function (Blueprint $table) {
            $table->dropIndex(['proceso_eleccion']);
        });
    }
};