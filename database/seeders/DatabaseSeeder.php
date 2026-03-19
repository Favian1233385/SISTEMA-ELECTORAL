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
            'name' => 'Administrador',
            'email' => 'admin123@gmail.com',
            'password' => bcrypt('admin12344'), // Pon la clave que desees aquí
        ]);

        // Llamamos al seeder de los territorios que ya tenías
        $this->call([
            EcuadorTerritorioSeeder::class,
        ]);
    }
}
