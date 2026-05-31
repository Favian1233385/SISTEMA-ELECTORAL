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
        
        // El CNE suele usar filas superiores de títulos informativos. 
        // array_slice($import[0], 9) saltará las primeras 9 líneas para empezar justo en la cabecera/datos (Fila 10)
        $datos = array_slice($import[0], 9); 

        DB::beginTransaction();
        try {
            foreach ($datos as $fila) {
                // Saltar filas totalmente vacías
                if (empty($fila) || !isset($fila[1]) || empty($fila[1])) continue;

                $nombreProvinciaExcel = trim(strtoupper($fila[1])); // Columna B: PROVINCIA

                // Filtro estricto por la provincia seleccionada en la interfaz web
                if ($nombreProvinciaExcel !== $nombreProvinciaFiltro) {
                    continue;
                }

                // 1. Registrar o encontrar Provincia (Normalizado a Mayúsculas)
                $provincia = Provincia::firstOrCreate([
                    'nombre' => trim(strtoupper($fila[1]))
                ]);

                // 2. Registrar o encontrar Cantón
                $canton = Canton::firstOrCreate([
                    'nombre'       => trim(strtoupper($fila[5])), // Columna F: CANTÓN
                    'provincia_id' => $provincia->id
                ]);

                // 3. Registrar o encontrar Parroquia
                $parroquia = Parroquia::firstOrCreate([
                    'nombre'    => trim(strtoupper($fila[7])), // Columna H: PARROQUIA
                    'canton_id' => $canton->id
                ]);

                // 4. Registrar o encontrar Recinto Electoral (Mapeado desde la Columna K: ZONA/RECIENTO)
                $nombreRecinto = (!empty($fila[10]) && trim($fila[10]) !== '0') ? trim($fila[10]) : 'RECENTO UNICO ' . trim($fila[7]);
                
                $recinto = Recinto::firstOrCreate(
                    [
                        'nombre'       => trim(strtoupper($nombreRecinto)),
                        'parroquia_id' => $parroquia->id
                    ],
                    [
                        'direccion'    => 'Sin dirección' // El distributivo de zonas no incluye calle, se asigna por defecto
                    ]
                );

                // 5. PROCESAR POBLACIÓN DE JUNTAS FEMENINAS
                $juntasFem = (int)($fila[16] ?? 0); // Columna Q: JUNTAS MUJERES
                $electoresFem = (int)($fila[13] ?? 0); // Columna N: NUM ELECT MUJERES
                
                if ($juntasFem > 0) {
                    $this->generarBloqueJuntas(
                        $recinto->id, 
                        $juntasFem, 
                        $electoresFem, 
                        'FEMENINO', 
                        $procesoEleccion
                    );
                }

                // 6. PROCESAR POBLACIÓN DE JUNTAS MASCULINAS
                $juntasMasc = (int)($fila[15] ?? 0); // Columna P: JUNTAS HOMBRE
                $electoresMasc = (int)($fila[12] ?? 0); // Columna M: NUM ELECT HOMBRE
                
                if ($juntasMasc > 0) {
                    $this->generarBloqueJuntas(
                        $recinto->id, 
                        $juntasMasc, 
                        $electoresMasc, 
                        'MASCULINO', 
                        $procesoEleccion
                    );
                }
            }

            DB::commit();
            return back()->with('success', '¡Estructura CNE de ' . $nombreProvinciaFiltro . ' importada al 100% sin errores!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error en procesamiento: ' . $e->getMessage() . ' - Línea: ' . $e->getLine());
        }
    }

    private function generarBloqueJuntas($recintoId, $totalJuntas, $totalElectoresGenero, $genero, $procesoEleccion)
    {
        // Distribución proporcional exacta del CNE por mesa
        $electoresPorMesa = ($totalJuntas > 0) ? (int)floor($totalElectoresGenero / $totalJuntas) : 0;
        $residuoElectores = ($totalJuntas > 0) ? ($totalElectoresGenero % $totalJuntas) : 0;

        for ($i = 1; $i <= $totalJuntas; $i++) {
            $numeroMesa = str_pad($i, 3, "0", STR_PAD_LEFT);
            
            // Si la división no es exacta, le sumamos 1 elector sobrante a las primeras mesas hasta agotar el residuo
            $electoresFinales = $electoresPorMesa;
            if ($residuoElectores > 0) {
                $electoresFinales += 1;
                $residuoElectores--;
            }

            // 1. Inserción limpia con Género Estricto en Mayúsculas
            $mesa = Mesa::updateOrCreate(
                [
                    'recinto_id'       => $recintoId,
                    'numero'           => $numeroMesa,
                    'genero'           => $genero, // Guardará 'MASCULINO' o 'FEMENINO'
                    'proceso_eleccion' => $procesoEleccion 
                ],
                [
                    'num_electores'    => $electoresFinales,
                    'estado'           => 'Habilitada'
                ]
            );

            // 2. Generación automática de Credenciales del Digitador
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