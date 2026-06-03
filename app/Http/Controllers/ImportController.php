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
        $request->validate([
            'file'             => 'required|mimes:xlsx,xls,csv,txt',
            'proceso_eleccion' => 'required|in:generales,primarias',
            'provincia_id'     => 'required|exists:provincias,id'
        ]);

        $procesoEleccion = $request->input('proceso_eleccion'); 
        $provinciaSeleccionada = Provincia::findOrFail($request->input('provincia_id'));
        $nombreProvinciaFiltro = trim(strtoupper($provinciaSeleccionada->nombre));

        $import = Excel::toArray([], $request->file('file'));
        $hoja = $import[0] ?? [];

        if (empty($hoja)) {
            return back()->with('error', 'El archivo Excel está vacío.');
        }

        DB::beginTransaction();
        try {

            // =========================================================================
            // FLUJO A: PROCESAMIENTO DE ELECCIONES PRIMARIAS (ESTRUCTURA VARIABLE)
            // =========================================================================
            if ($procesoEleccion === 'primarias') {
                
                // En primarias asumimos que la fila 0 o 1 contiene las cabeceras. Buscamos dónde empiezan los datos.
                $inicioDatos = 0;
                foreach ($hoja as $index => $fila) {
                    // Detectamos la cabecera si contiene palabras clave comunes
                    if (isset($fila[0]) && (str_contains(strinfo($fila[0]), 'CANT') || str_contains(strinfo($fila[1]), 'PARR') || str_contains(strinfo($fila[2]), 'RECINTO'))) {
                        $inicioDatos = $index + 1;
                        break;
                    }
                    // Si no encuentra cabecera explícita, por defecto evalúa que los datos inician en la fila 1
                    if ($index == 1) { $inicioDatos = 1; }
                }

                $datos = array_slice($hoja, $inicioDatos);

                foreach ($datos as $fila) {
                    // Validar que la fila tenga contenido mínimo
                    if (empty($fila) || !isset($fila[0]) || empty(trim($fila[0]))) continue;

                    // Dinamismo: Mapeo posicional secuencial típico de archivos de partidos
                    // Columna 0: Cantón | Columna 1: Parroquia | Columna 2: Recinto Centralizado | Columna 3: No. Mesa
                    $nombreCanton    = trim(strtoupper($fila[0] ?? ''));
                    $nombreParroquia = trim(strtoupper($fila[1] ?? ''));
                    $nombreRecinto   = trim(strtoupper($fila[2] ?? 'CENTRALIZADO'));
                    $numeroMesaRaw   = trim($fila[3] ?? '1');
                    $totalElectores  = (int)($fila[4] ?? 350); // Si no viene, estandariza 350 por mesa

                    if (empty($nombreCanton) || empty($nombreParroquia)) continue;

                    // 1. Vincular a la provincia seleccionada en la Web
                    $canton = Canton::firstOrCreate([
                        'nombre'       => $nombreCanton,
                        'provincia_id' => $provinciaSeleccionada->id
                    ]);

                    // 2. Registrar/Encontrar Parroquia
                    $parroquia = Parroquia::firstOrCreate([
                        'nombre'    => $nombreParroquia,
                        'canton_id' => $canton->id
                    ]);

                    // 3. Registrar/Encontrar Recinto Unificado de las Primarias
                    $recinto = Recinto::firstOrCreate([
                        'nombre'       => $nombreRecinto,
                        'parroquia_id' => $parroquia->id
                    ], [
                        'direccion'    => 'Sede del Partido / Recinto Centralizado'
                    ]);

                    // 4. Inserción directa de la Mesa Única de Primarias
                    $numeroMesa = str_pad($numeroMesaRaw, 3, "0", STR_PAD_LEFT);

                    $mesa = Mesa::updateOrCreate(
                        [
                            'recinto_id'       => $recinto->id,
                            'numero'           => $numeroMesa,
                            'genero'           => 'UNICO', // En primarias no suele dividirse por sexo
                            'proceso_eleccion' => 'primarias'
                        ],
                        [
                            'num_electores'    => $totalElectores,
                            'estado'           => 'Habilitada'
                        ]
                    );

                    // 5. Crear credencial para el digitador de esta mesa de primarias
                    $identificador = "r" . $recinto->id . "m" . $numeroMesa . "u_primarias";
                    $emailFalso = $identificador . "@sistema.com";

                    User::updateOrCreate(
                        ['email' => $emailFalso], 
                        [
                            'name'             => "Digitador Primarias Mesa " . $numeroMesa . " - " . substr($nombreParroquia, 0, 10),
                            'password'         => Hash::make('12345678'),
                            'role'             => 'digitador',
                            'proceso_eleccion' => 'primarias',
                            'mesa_id'          => $mesa->id
                        ]
                    );
                }

            // =========================================================================
            // FLUJO B: PROCESAMIENTO ESTRUCTURA OFICIAL CNE (ELECCIONES GENERALES)
            // =========================================================================
            } else {
                
                $datos = array_slice($hoja, 9); // Salto estricto de las 9 filas informativas del CNE

                foreach ($datos as $fila) {
                    if (empty($fila) || !isset($fila[1]) || empty($fila[1])) continue;

                    $nombreProvinciaExcel = trim(strtoupper($fila[1])); 

                    if ($nombreProvinciaExcel !== $nombreProvinciaFiltro) {
                        continue;
                    }

                    $provincia = Provincia::firstOrCreate([
                        'nombre' => trim(strtoupper($fila[1]))
                    ]);

                    $canton = Canton::firstOrCreate([
                        'nombre'       => trim(strtoupper($fila[5])), 
                        'provincia_id' => $provincia->id
                    ]);

                    $parroquia = Parroquia::firstOrCreate([
                        'nombre'    => trim(strtoupper($fila[7])), 
                        'canton_id' => $canton->id
                    ]);

                    $nombreRecinto = (!empty($fila[10]) && trim($fila[10]) !== '0') ? trim($fila[10]) : 'RECENTO UNICO ' . trim($fila[7]);
                    
                    $recinto = Recinto::firstOrCreate(
                        [
                            'nombre'       => trim(strtoupper($nombreRecinto)),
                            'parroquia_id' => $parroquia->id
                        ],
                        ['direccion'    => 'Sin dirección']
                    );

                    // Juntas Femeninas CNE
                    $juntasFem = (int)($fila[16] ?? 0); 
                    $electoresFem = (int)($fila[13] ?? 0); 
                    if ($juntasFem > 0) {
                        $this->generarBloqueJuntas($recinto->id, $juntasFem, $electoresFem, 'FEMENINO', 'generales');
                    }

                    // Juntas Masculinas CNE
                    $juntasMasc = (int)($fila[15] ?? 0); 
                    $electoresMasc = (int)($fila[12] ?? 0); 
                    if ($juntasMasc > 0) {
                        $this->generarBloqueJuntas($recinto->id, $juntasMasc, $electoresMasc, 'MASCULINO', 'generales');
                    }
                }
            }

            DB::commit();
            return back()->with('success', '¡Estructura de ' . ($procesoEleccion === 'primarias' ? 'Primarias Internas' : 'Generales CNE') . ' procesada con éxito!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error en procesamiento: ' . $e->getMessage() . ' - Línea: ' . $e->getLine());
        }
    }

    private function generarBloqueJuntas($recintoId, $totalJuntas, $totalElectoresGenero, $genero, $procesoEleccion)
    {
        $electoresPorMesa = ($totalJuntas > 0) ? (int)floor($totalElectoresGenero / $totalJuntas) : 0;
        $residuoElectores = ($totalJuntas > 0) ? ($totalElectoresGenero % $totalJuntas) : 0;

        for ($i = 1; $i <= $totalJuntas; $i++) {
            $numeroMesa = str_pad($i, 3, "0", STR_PAD_LEFT);
            
            $electoresFinales = $electoresPorMesa;
            if ($residuoElectores > 0) {
                $electoresFinales += 1;
                $residuoElectores--;
            }

            $mesa = Mesa::updateOrCreate(
                [
                    'recinto_id'       => $recintoId,
                    'numero'           => $numeroMesa,
                    'genero'           => $genero, 
                    'proceso_eleccion' => $procesoEleccion 
                ],
                [
                    'num_electores'    => $electoresFinales,
                    'estado'           => 'Habilitada'
                ]
            );

            $identificador = "r" . $recintoId . "m" . $numeroMesa . strtolower(substr($genero, 0, 1)) . "_" . $procesoEleccion;
            $emailFalso = $identificador . "@sistema.com";

            User::updateOrCreate(
                ['email' => $emailFalso], 
                [
                    'name'             => "Digitador " . ucfirst(strtolower($genero)) . " Mesa " . $numeroMesa . " (" . ucfirst($procesoEleccion) . ")",
                    'password'         => Hash::make('12345678'),
                    'role'             => 'digitador',
                    'proceso_eleccion' => $procesoEleccion,
                    'mesa_id'          => $mesa->id
                ]
            );
        }
    }
}

// Función auxiliar para normalizar búsquedas de cabeceras libres de texto
if (!function_exists('strinfo')) {
    function strinfo($value) {
        return strtoupper(trim(stringf($value)));
    }
}
if (!function_exists('stringf')) {
    function stringf($value) {
        return is_array($value) ? '' : (string)$value;
    }
}