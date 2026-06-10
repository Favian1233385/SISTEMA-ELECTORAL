<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
        public function run()
    {
        // Crear el usuario administrador automáticamente
        \App\Models\User::create([
            'name'     => 'Administrador',
            'email'    => 'raul_198867@hotmail.com', // <-- COLOCA TU CORREO REAL AQUÍ
            'password' => Hash::make('Arutam345@*Sistema'), // <-- COLOCA TU NUEVA CLAVE SEGURA AQUÍ
            'role'     => 'admin', // Garantiza que tenga el rol que tu controlador busca
        ]);

        // Llamamos al seeder de los territorios que ya tenías
        $this->call([
            EcuadorTerritorioSeeder::class,
            ProvinciaSeeder::class,
        ]);
    }
}
