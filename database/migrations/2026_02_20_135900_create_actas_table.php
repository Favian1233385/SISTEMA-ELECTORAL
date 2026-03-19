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
        Schema::create('actas', function (Blueprint $table) {
        $table->id();
        // Quitamos unique() para permitir un acta de Alcalde y otra de Prefecto para la misma mesa
        $table->foreignId('mesa_id')->constrained('mesas'); 
        $table->foreignId('user_id')->constrained('users'); 
        
        // AGREGAMOS el campo dignidad que faltaba
        $table->string('dignidad'); 

        $table->integer('votos_blancos')->default(0);
        $table->integer('votos_nulos')->default(0);
        $table->string('foto_path')->nullable(); 
        $table->enum('estado', ['ingresada', 'verificada', 'con_novedad'])->default('ingresada');
        
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
        Schema::dropIfExists('actas');
    }
};
