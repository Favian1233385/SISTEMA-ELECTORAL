<?php

namespace Database\Seeders;

use App\Models\Provincia;
use App\Models\Canton;
use App\Models\Parroquia;
use Illuminate\Database\Seeder;

class EcuadorTerritorioSeeder extends Seeder
{
    public function run(): void
    {
        // Creamos la Provincia de Morona Santiago
        $moronaSantiago = Provincia::firstOrCreate(['nombre' => 'Morona Santiago']);

        // Listado real de Cantones y sus Parroquias principales
        $datos = [
            'Morona' => ['Macas', 'Alshi', 'General Proaño', 'San Isidro', 'Sinaí', 'Zuñac', 'Río Blanco'],
            'Sevilla Don Bosco' => ['Sevilla Don Bosco'],
            'Gualaquiza' => ['Gualaquiza', 'Amazonas', 'Bermejos', 'Bomboiza', 'Chigüinda', 'El Rosario'],
            'Limón Indanza' => ['General Leonidas Plaza Gutiérrez', 'Indanza', 'San Miguel de Conchay', 'Santa Susana de Chiviaza'],
            'Palora' => ['Palora', 'Sangay', '16 de Agosto', 'Arapicos', 'Cumandá'],
            'Santiago' => ['Santiago de Méndez', 'Copal', 'Chupianza', 'Patuca', 'San Luis de El Acho', 'Tayuza'],
            'Sucúa' => ['Sucúa', 'Asunción', 'Huambi', 'Santa Marianita de Jesús'],
            'Huamboya' => ['Huamboya', 'Chiguaza'],
            'San Juan Bosco' => ['San Juan Bosco', 'Pan de Azúcar', 'San Carlos de Limón', 'Santiago de Pananza'],
            'Taisha' => ['Taisha', 'Huasaga', 'Macuma', 'Tuutinentza'],
            'Logroño' => ['Logroño', 'Yaupi', 'Shimpis'],
            'Pablo Sexto' => ['Pablo Sexto'],
            'Tiwintza' => ['Santiago', 'San José de Morona'],
        ];

        foreach ($datos as $cantonNombre => $parroquias) {
            $canton = Canton::firstOrCreate([
                'nombre' => $cantonNombre,
                'provincia_id' => $moronaSantiago->id
            ]);

            foreach ($parroquias as $parroquiaNombre) {
                Parroquia::firstOrCreate([
                    'nombre' => $parroquiaNombre,
                    'canton_id' => $canton->id
                ]);
            }
        }
    }
}