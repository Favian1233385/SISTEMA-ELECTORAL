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
        Schema::create('candidatos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partido_id')->constrained('partidos')->onDelete('cascade');
            
            // Tipo de dignidad: Prefecto, Alcalde, Concejal, etc.
            $table->enum('dignidad', ['Prefecto', 'Alcalde', 'Concejal Urbano', 'Concejal Rural', 'Vocal Parroquial']);
            
            $table->string('nombre', 150);
            $table->string('foto')->nullable(); // Ruta de la foto del candidato
            
            // Estos campos vinculan al candidato con su área de competencia
            $table->unsignedBigInteger('provincia_id')->nullable();
            $table->unsignedBigInteger('canton_id')->nullable();
            $table->unsignedBigInteger('parroquia_id')->nullable();

            $table->timestamps();

            // Índices para mejorar la velocidad de búsqueda
            $table->foreign('provincia_id')->references('id')->on('provincias');
            $table->foreign('canton_id')->references('id')->on('cantones');
            $table->foreign('parroquia_id')->references('id')->on('parroquias');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('candidatos');
    }
};
