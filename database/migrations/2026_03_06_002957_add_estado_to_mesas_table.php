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
        Schema::table('mesas', function (Blueprint $table) {
            // Añadimos el estado, por defecto 'Habilitada'
            $table->string('estado')->default('Habilitada')->after('num_electores');
        });
    }

    public function down()
    {
        Schema::table('mesas', function (Blueprint $table) {
            $table->dropColumn('estado');
        });
    }
};
