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
            // Permiso para que un Cantonal vea la Provincia o un Parroquial vea el Cantón
            $table->boolean('ver_nivel_superior')->default(false); 
            // Permiso para que un Cantonal vea sus Parroquias (hacia abajo)
            $table->boolean('ver_nivel_inferior')->default(false);
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
            //
        });
    }
};
