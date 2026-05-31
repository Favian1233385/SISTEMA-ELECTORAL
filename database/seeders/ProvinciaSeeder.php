<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Provincia;

class ProvinciaSeeder extends Seeder
{
    public function run(): void
    {
        $provincias = [
            ['nombre' => 'AZUAY'],
            ['nombre' => 'BOLIVAR'],
            ['nombre' => 'CAÑAR'],
            ['nombre' => 'CARCHI'],
            ['nombre' => 'COTOPAXI'],
            ['nombre' => 'CHIMBORAZO'],
            ['nombre' => 'EL ORO'],
            ['nombre' => 'ESMERALDAS'],
            ['nombre' => 'GUAYAS'],
            ['nombre' => 'IMBABURA'],
            ['nombre' => 'LOJA'],
            ['nombre' => 'LOS RIOS'],
            ['nombre' => 'MANABI'],
            ['nombre' => 'MORONA SANTIAGO'],
            ['nombre' => 'NAPO'],
            ['nombre' => 'PASTAZA'],
            ['nombre' => 'PICHINCHA'],
            ['nombre' => 'TUNGURAHUA'],
            ['nombre' => 'ZAMORA CHINCHIPE'],
            ['nombre' => 'GALAPAGOS'],
            ['nombre' => 'SUCUMBIOS'],
            ['nombre' => 'ORELLANA'],
            ['nombre' => 'SANTO DOMINGO DE LOS TSÁCHILAS'],
            ['nombre' => 'SANTA ELENA'],
        ];

        foreach ($provincias as $provincia) {
            Provincia::firstOrCreate(['nombre' => $provincia['nombre']]);
        }
    }
}