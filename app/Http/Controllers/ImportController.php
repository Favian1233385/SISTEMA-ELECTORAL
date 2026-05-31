<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Provincia;
use App\Models\Canton;
use App\Models\Parroquia;
use App\Models\Recinto;
use App\Models\Mesa;
use App\Models\User;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ImportController extends Controller
{
    public function import(Request $request)
    {
        // 1. Validamos el archivo, el proceso (estrictamente en plural) y la provincia elegida desde la interfaz
        $request->validate([
            'file'             => 'required|mimes:xlsx,xls,csv,txt',
            'proceso_eleccion' => 'required|in:generales,primarias',
            'provincia_id'     => 'required|exists:provincias,id' // Captura el ID de la provincia desde la interfaz web
        ]);

        $procesoEleccion = $request->input('proceso_eleccion'); // Guardará 'generales' o 'primarias'
        
        // Buscamos el nombre real de la provincia seleccionada en la web para usarlo como filtro estricto
        $provinciaSeleccionada = Provincia::findOrFail($request->input('provincia_id'));
        $nombreProvinciaFiltro = trim(strtoupper($provinciaSeleccionada->nombre));

        $import = Excel::toArray([], $request->file('file'));

        // Extraemos la hoja y eliminamos el encabezado
        $datos = array_slice($import[0], 1); 

        DB::beginTransaction();
        try {
            foreach ($datos as $fila) {

                // Saltar filas vacías o sin provincia
                if (empty($fila) || empty($fila[0])) continue;

                $nombreProvinciaExcel = trim(strtoupper($fila[0])); // Columna A

                // ⚠️ FILTRO CRÍTICO SAAS INTERFAZ: 
                // Compara la fila del Excel con la provincia seleccionada. Si no coincide, salta a la siguiente.
                if ($nombreProvinciaExcel !== $nombreProvinciaFiltro) {
                    continue;
                }

                // 1. Provincia — columna A
                $provincia = Provincia::firstOrCreate([
                    'nombre' => trim($fila[0])
                ]);

                // 2. Cantón — columna B
                $canton = Canton::firstOrCreate([
                    'nombre'       => trim($fila[1]),
                    'provincia_id' => $provincia->id
                ]);

                // 3. Parroquia — columna C
                $parroquia = Parroquia::firstOrCreate([
                    'nombre'    => trim($fila[2]),
                    'canton_id' => $canton->id
                ]);

                // 4. Recinto — columna D | Dirección — columna E
                $recinto = Recinto::firstOrCreate(
                    [
                        'nombre'       => trim($fila[3]),
                        'parroquia_id' => $parroquia->id
                    ],
                    [
                        'direccion' => isset($fila[4]) && $fila[4] !== '' 
                                        ? trim($fila[4]) 
                                        : 'Sin dirección'
                    ]
                );

                // 5. Juntas femeninas (Recuperamos tus índices exactos y tu lógica de cálculo original)
                $this->crearJuntas(
                    $recinto->id,
                    $fila[5] ?? 0,   
                    $fila[6] ?? 0,   
                    'Femenino',
                    $fila[8] ?? 0,
                    $procesoEleccion
                );

                // 6. Juntas masculinas (Recuperamos tus índices exactos y tu lógica de cálculo original)
                $this->crearJuntas(
                    $recinto->id,
                    $fila[6] ?? 0,   
                    $fila[7] ?? 0,   
                    'Masculino',
                    $fila[8] ?? 0,
                    $procesoEleccion
                );
            }

            DB::commit();
            
            $mensaje = '¡Importación de estructura de ' . $nombreProvinciaFiltro . ' para Elecciones ' . ucfirst($procesoEleccion) . ' completada con éxito!';
            return back()->with('success', $mensaje);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Fallo en la importación: ' . $e->getMessage() . ' en la línea ' . $e->getLine());
        }
    }

    private function crearJuntas($recintoId, $inicio, $fin, $genero, $totalElectoresRecinto, $procesoEleccion)
    {
        $inicio = (int)$inicio;
        $fin = (int)$fin;

        if ($inicio > 0 && $fin > 0) {
            $numJuntas = ($fin - $inicio) + 1;
            
            $totalElectoresRecinto = filter_var($totalElectoresRecinto, FILTER_VALIDATE_INT) !== false ? (int)$totalElectoresRecinto : 0;
            
            // Mantenemos tu cálculo de proporcionalidad real por recinto
            $electoresPorMesa = $numJuntas > 0 && $totalElectoresRecinto > 0 
                ? (int)round($totalElectoresRecinto / $numJuntas) 
                : 350; // Estándar CNE Ecuador en caso de ausencia de datos

            for ($i = $inicio; $i <= $fin; $i++) {
                $numeroMesa = str_pad($i, 3, "0", STR_PAD_LEFT);
                
                // 1. Crear o actualizar la Mesa (Guardando estrictamente en plural)
                $mesa = Mesa::updateOrCreate(
                    [
                        'recinto_id'       => $recintoId,
                        'numero'           => $numeroMesa,
                        'genero'           => $genero,
                        'proceso_eleccion' => $procesoEleccion 
                    ],
                    [
                        'num_electores'    => $electoresPorMesa,
                        'estado'           => 'Habilitada'
                    ]
                );

                // 2. Crear las credenciales del digitador con el sufijo del proceso en plural
                $identificador = "r" . $recintoId . "m" . $numeroMesa . strtolower(substr($genero, 0, 1)) . "_" . $procesoEleccion;
                $emailFalso = $identificador . "@sistema.com";

                // 3. Crear o actualizar el Usuario vinculando la columna unificada 'proceso_eleccion' en plural
                User::updateOrCreate(
                    ['email' => $emailFalso], 
                    [
                        'name'             => "Digitador " . $genero . " Mesa " . $numeroMesa . " (" . ucfirst($procesoEleccion) . ")",
                        'password'         => Hash::make('12345678'),
                        'role'             => 'digitador',
                        'proceso_eleccion' => $procesoEleccion, // ¡CORREGIDO SIMÉTRICAMENTE! Guarda 'generales' o 'primarias'
                        'mesa_id'          => $mesa->id
                    ]
                );
            }
        }
    }
}