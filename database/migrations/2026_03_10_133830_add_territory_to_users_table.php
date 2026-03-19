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
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Definimos el rol (por defecto digitador)
            $table->string('role')->default('digitador')->after('email');

            // Llaves foráneas: Deben coincidir con los nombres de tus tablas
            // Si tus tablas se llaman 'provincias', 'cantones' y 'parroquias':
            $table->foreignId('provincia_id')->nullable()->after('role')->constrained('provincias')->onDelete('set null');
            $table->foreignId('canton_id')->nullable()->after('provincia_id')->constrained('cantones')->onDelete('set null');
            $table->foreignId('parroquia_id')->nullable()->after('canton_id')->constrained('parroquias')->onDelete('set null');

            // Permiso especial para ver prefectos (booleano)
            $table->boolean('ver_prefectos')->default(false)->after('parroquia_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            // Es importante definir cómo revertir el cambio
            $table->dropForeign(['provincia_id']);
            $table->dropForeign(['canton_id']);
            $table->dropForeign(['parroquia_id']);
            $table->dropColumn(['role', 'provincia_id', 'canton_id', 'parroquia_id', 'ver_prefectos']);
        });
    }
};
