<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('actas', function (Blueprint $blueprint) {
            // Se agrega la columna después de la dignidad, con un valor por defecto si es necesario
            $blueprint->string('tipo_proceso')->default('generales')->after('dignidad');
        });
    }

    public function down()
    {
        Schema::table('actas', function (Blueprint $blueprint) {
            $blueprint->dropColumn('tipo_proceso');
        });
    }
};