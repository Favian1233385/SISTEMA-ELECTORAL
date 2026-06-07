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
            // FLUJO A: PROCESAMIENTO DE ELECCIONES PRIMARIAS (ESTRUCTURA REAL)
            // =========================================================================
            if ($procesoEleccion === 'primarias') {
                
                $procesoActivo = \App\Models\ProcesoElectoral::where('estado', 'activo')->first();
                $procesoId = $procesoActivo ? $procesoActivo->id : 2; 

                // Omitimos la primera fila (índice 0) que contiene las cabeceras mostradas en la imagen
                $datos = array_slice($hoja, 1);

                foreach ($datos as $fila) {
                    // Validar que la fila tenga al menos datos de la provincia
                    if (empty($fila) || !isset($fila[0]) || empty(trim($fila[0]))) continue;

                    // Mapeo indexado según la captura real del archivo Excel (image_c08ee3.jpg)
                    $nombreProvinciaExcel = trim(strtoupper($fila[0] ?? ''));
                    $nombreCanton         = trim(strtoupper($fila[1] ?? ''));
                    $nombreParroquia      = trim(strtoupper($fila[2] ?? ''));
                    
                    // Filtrar por la provincia seleccionada en el formulario web
                    if ($nombreProvinciaExcel !== $nombreProvinciaFiltro) {
                        continue;
                    }

                    // Saltar filas corruptas o vacías en áreas clave
                    if (empty($nombreCanton) || empty($nombreParroquia)) continue;

                    // Manejo del Recinto (Columna D -> Índice 3)
                    $nombreRecintoRaw = trim($fila[3] ?? '');
                    $nombreRecinto = (!empty($nombreRecintoRaw) && $nombreRecintoRaw !== '0') 
                        ? strtoupper($nombreRecintoRaw) 
                        : 'RECINTO UNICO ' . $nombreParroquia;

                    // -----------------------------------------------------------------
                    // PERSISTENCIA DE UBICACIONES GEOGRÁFICAS
                    // -----------------------------------------------------------------
                    $canton = Canton::firstOrCreate([
                        'nombre'       => $nombreCanton,
                        'provincia_id' => $provinciaSeleccionada->id
                    ]);

                    $parroquia = Parroquia::firstOrCreate([
                        'nombre'               => $nombreParroquia,
                        'canton_id'            => $canton->id,
                        'proceso_electoral_id' => $procesoId
                    ]);

                    $recinto = Recinto::firstOrCreate([
                        'nombre'               => $nombreRecinto,
                        'parroquia_id'         => $parroquia->id,
                        'proceso_electoral_id' => $procesoId
                    ], [
                        'direccion'            => 'Sede o Espacio Cubierto de la Parroquia'
                    ]);

                    // -----------------------------------------------------------------
                    // LECTURA DINÁMICA DE GÉNEROS DESDE EL EXCEL (image_c08ee3.jpg)
                    // -----------------------------------------------------------------
                    
                    // Bloque Masculino: Columnas F (Índice 5) e I (Índice 8)
                    $electoresMasc = (int)($fila[5] ?? 0);
                    $juntasMasc    = (int)($fila[8] ?? 0);
                    
                    if ($juntasMasc > 0) {
                        $this->generarBloqueJuntas($recinto->id, $juntasMasc, $electoresMasc, 'MASCULINO', 'primarias');
                    }

                    // Bloque Femenino: Columnas G (Índice 6) y J (Índice 9)
                    $electoresFem = (int)($fila[6] ?? 0);
                    $juntasFem    = (int)($fila[9] ?? 0);
                    
                    if ($juntasFem > 0) {
                        $this->generarBloqueJuntas($recinto->id, $juntasFem, $electoresFem, 'FEMENINO', 'primarias');
                    }
                }
            }

            // =========================================================================
            // FLUJO B: PROCESAMIENTO ESTRUCTURA OFICIAL CNE (ELECCIONES GENERALES)
            // =========================================================================
            elseif ($procesoEleccion === 'generales') {
                
                $datos = array_slice($hoja, 9); 

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
        // Forzar mayúsculas sostenidas y limpiar espacios ('MASCULINO' o 'FEMENINO')
        $generoFormateado = strtoupper(trim($genero)); 

        $electoresPorMesa = ($totalJuntas > 0) ? (int)floor($totalElectoresGenero / $totalJuntas) : 0;
        $residuoElectores = ($totalJuntas > 0) ? ($totalElectoresGenero % $totalJuntas) : 0;

        for ($i = 1; $i <= $totalJuntas; $i++) {
            $numeroMesa = str_pad($i, 3, "0", STR_PAD_LEFT);
            
            $electoresFinales = $electoresPorMesa;
            if ($residuoElectores > 0) {
                $electoresFinales += 1;
                $residuoElectores--;
            }

            // updateOrCreate buscará por la combinación exacta de estos 4 campos
            $mesa = Mesa::updateOrCreate(
                [
                    'recinto_id'       => $recintoId,
                    'numero'           => $numeroMesa,
                    'genero'           => $generoFormateado, // Guarda 'MASCULINO' o 'FEMENINO'
                    'proceso_eleccion' => $procesoEleccion   // Guarda 'primarias' o 'generales'
                ],
                [
                    'num_electores'    => $electoresFinales,
                    'estado'           => 'Habilitada'
                ]
            );

            // Identificador único para el correo electrónico del digitador
            $sufijoGenero = ($generoFormateado === 'MASCULINO') ? 'm' : 'f';
            $identificador = "r" . $recintoId . "m" . $numeroMesa . $sufijoGenero . "_" . $procesoEleccion;
            $emailFalso = $identificador . "@sistema.com";

            User::updateOrCreate(
                ['email' => $emailFalso], 
                [
                    'name'             => "Digitador " . ucfirst(strtolower($generoFormateado)) . " Mesa " . $numeroMesa . " (" . ucfirst($procesoEleccion) . ")",
                    'password'         => Hash::make('12345678'),
                    'role'             => 'digitador',
                    'proceso_eleccion' => $procesoEleccion,
                    'mesa_id'          => $mesa->id
                ]
            );
        }
    }
}
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