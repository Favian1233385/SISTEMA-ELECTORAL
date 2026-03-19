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
        Schema::create('votos', function (Blueprint $table) {
            $table->id();
            // Relación con el Acta (Cabecera)
            $table->foreignId('acta_id')->constrained('actas')->onDelete('cascade');
            $table->foreignId('candidato_id')->constrained('candidatos')->onDelete('cascade');

            // Solo los votos del candidato
            $table->integer('conteo_votos')->default(0);
            $table->timestamps();

            // Integridad: Un candidato solo puede tener un registro por acta/mesa
            $table->unique(['acta_id', 'candidato_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('votos');
    }
};
