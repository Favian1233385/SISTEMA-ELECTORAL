<?php

use App\Http\Controllers\{
    ProfileController,
    PartidoController,
    CandidatoController,
    TerritorioController,
    ActaController,
    UserController,
    JurisdiccionConfigController,
    ResultadoController // Correcto
};
use Illuminate\Support\Facades\Route;

// --- RUTAS PÚBLICAS ---
Route::get('/', function () { return view('welcome'); });

// --- RUTAS PROTEGIDAS (Requieren estar logueado) ---
Route::middleware(['auth', 'verified'])->group(function () {
    
    // ESTA ES LA RUTA QUE DEBES USAR PARA VER LOS RESULTADOS CORREGIDOS
    // URL: tu-dominio.com/resultados
    Route::get('/resultados', [ResultadoController::class, 'index'])->name('resultados.index');

    // Detalle de votos por candidato (La que acabamos de agregar)
    Route::get('/resultados/detalle/{candidato}', [ResultadoController::class, 'detalle'])->name('resultados.detalle');
    
    // El Dashboard sigue igual
    Route::get('/dashboard', [TerritorioController::class, 'dashboard'])->name('dashboard');
    
    // --- RUTAS DE ACTAS ---
    Route::get('/actas', [ActaController::class, 'index'])->name('actas.index');
    Route::get('/actas/crear', [ActaController::class, 'create'])->name('actas.create');
    Route::get('/actas/{acta}', [ActaController::class, 'show'])->name('actas.show');

    // API para Selects Dinámicos
    Route::prefix('api')->group(function () {
        Route::get('/cantones/{provincia_id}', [ActaController::class, 'getCantones']);
        Route::get('/parroquias/{canton_id}', [ActaController::class, 'getParroquias']);
        Route::get('/recintos/{parroquia_id}', [ActaController::class, 'getRecintos']);
        Route::get('/mesas/{recinto_id}', [ActaController::class, 'getMesas']);
        
        Route::get('/cantones/{canton}/parroquias', function($cantonId) {
            return App\Models\Parroquia::where('canton_id', $cantonId)
                ->select('id', 'nombre')
                ->orderBy('nombre', 'asc')
                ->get();
        });
    });

    // --- BLOQUE: SOLO DIGITADORES ---
    Route::middleware(['solo.digitadores'])->group(function () {
        Route::post('/actas', [ActaController::class, 'store'])->name('actas.store');
    });
    
    // --- BLOQUE: SOLO ADMINISTRADORES ---
    Route::middleware(['admin'])->group(function () {
        Route::get('/estadisticas', [TerritorioController::class, 'dashboard'])->name('estadisticas.index');
        Route::resource('actas', ActaController::class)->except(['index', 'show', 'create', 'store']);
        Route::resource('partidos', PartidoController::class);
        Route::resource('candidatos', CandidatoController::class);

        // Gestión de Usuarios
        Route::get('/usuarios', [UserController::class, 'index'])->name('users.index');
        Route::get('/usuarios/crear', [UserController::class, 'create'])->name('users.create');
        Route::post('/usuarios/guardar', [UserController::class, 'store'])->name('users.store');
        Route::get('/usuarios/{user}/editar', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/usuarios/{user}', [UserController::class, 'update'])->name('users.update');
        
        Route::get('/configuracion-jurisdicciones', [JurisdiccionConfigController::class, 'index'])->name('jurisdiccion.config');
        Route::post('/jurisdiccion-update/{id}', [JurisdiccionConfigController::class, 'update'])->name('jurisdiccion.update');

        // Gestión de Territorio
        Route::get('/territorios', [TerritorioController::class, 'gestionarDivision'])->name('territorios.index');
        Route::post('/territorios/parroquia', [TerritorioController::class, 'storeParroquia'])->name('parroquia.store');
        Route::post('/territorios/recinto', [TerritorioController::class, 'storeRecinto'])->name('recinto.store');
        Route::post('/territorios/mesa', [TerritorioController::class, 'storeMesa'])->name('mesa.store');

        Route::put('/recinto/{id}', [TerritorioController::class, 'updateRecinto'])->name('recinto.update');
        Route::delete('/recinto/{id}', [TerritorioController::class, 'destroyRecinto'])->name('recinto.destroy');
        Route::put('/mesa/{id}', [TerritorioController::class, 'updateMesa'])->name('mesa.update');
        Route::delete('/mesa/{id}', [TerritorioController::class, 'destroyMesa'])->name('mesa.destroy');
    });

    // Perfil de usuario
    Route::controller(ProfileController::class)->group(function () {
        Route::get('/profile', 'edit')->name('profile.edit');
        Route::patch('/profile', 'update')->name('profile.update');
        Route::delete('/profile', 'destroy')->name('profile.destroy');
    });
});

require __DIR__.'/auth.php';