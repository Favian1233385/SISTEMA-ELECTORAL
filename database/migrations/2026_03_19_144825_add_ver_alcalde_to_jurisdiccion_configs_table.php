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
        Schema::table('jurisdiccion_configs', function (Blueprint $table) {
            // Se añade después de ver_provincia para mantener orden
            $table->boolean('ver_alcalde')->default(false)->after('ver_provincia');
        });
    }

    public function down()
    {
        Schema::table('jurisdiccion_configs', function (Blueprint $table) {
            $table->dropColumn('ver_alcalde');
        });
    }
};
