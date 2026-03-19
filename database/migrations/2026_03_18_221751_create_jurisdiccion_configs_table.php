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
    public function up() {
        Schema::create('jurisdiccion_configs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('canton_id')->nullable();
            $table->unsignedBigInteger('parroquia_id')->nullable();
            $table->boolean('ver_provincia')->default(false); // Hacia arriba
            $table->boolean('ver_parroquias')->default(false); // Hacia abajo
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('jurisdiccion_configs');
    }
};
