<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Provincia;
use App\Models\Canton;
use App\Models\Parroquia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index()
    {
        $auth = Auth::user();
        
        // Solo administradores pueden ver esta lista
        if (!$auth->esAdminGeneral() && !$auth->esAdminProvincial() && !$auth->esAdminCantonal()) {
            abort(403, 'No tienes permisos para gestionar usuarios.');
        }

        $query = User::query()->where('role', '!=', 'digitador');

        // Filtros de visualización según jerarquía
        if ($auth->esAdminGeneral()) {
            $users = $query->with(['provincia', 'canton', 'parroquia'])->get();
        } 
        elseif ($auth->esAdminProvincial()) {
            $users = $query->where('provincia_id', $auth->provincia_id)
                           ->whereNotIn('role', ['admin', 'admin_general', 'admin_provincial']) 
                           ->with(['canton', 'parroquia'])
                           ->get();
        } 
        elseif ($auth->esAdminCantonal()) {
            $users = $query->where('canton_id', $auth->canton_id)
                           ->whereNotIn('role', ['admin', 'admin_general', 'admin_provincial', 'admin_cantonal'])
                           ->with(['parroquia'])
                           ->get();
        }

        return view('users.index', compact('users'));
    }

    public function create()
    {
        // RESTRICCIÓN DE NEGOCIO: Solo el Súper Admin General puede crear personal administrativo
        if (!Auth::user()->esAdminGeneral()) {
            abort(403, 'Acceso denegado. Solo el Administrador General puede registrar nuevos usuarios.');
        }

        $provincias = Provincia::all();
        $cantones = collect(); // Se cargarán vía API en la vista
        $parroquias = collect();

        return view('users.create', compact('provincias', 'cantones', 'parroquias'));
    }

    public function store(Request $request)
    {
        // BLINDAJE EN SERVIDOR: Rechazar inserciones si no es Súper Admin
        if (!Auth::user()->esAdminGeneral()) {
            abort(403, 'No tienes permisos para realizar esta acción.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:admin,admin_provincial,admin_cantonal,admin_parroquial',
            'provincia_id' => 'nullable|exists:provincias,id',
            'canton_id' => 'nullable|exists:cantones,id',
            'parroquia_id' => 'nullable|exists:parroquias,id',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'dignidad_asignada' => $request->dignidad_asignada ?? 'todas',
            'provincia_id' => $request->provincia_id,
            'canton_id' => $request->canton_id,
            'parroquia_id' => $request->parroquia_id,
        ]);

        return redirect()->route('users.index')->with('success', 'Usuario administrativo registrado con éxito.');
    }

    public function edit(User $user)
    {
        // Solo el Súper Admin puede editar usuarios
        if (!Auth::user()->esAdminGeneral()) {
            abort(403, 'No tienes permisos para editar usuarios.');
        }

        $provincias = Provincia::all();
        
        // Para que el formulario cargue con los datos territoriales actuales del usuario a editar
        $cantones = $user->provincia_id ? Canton::where('provincia_id', $user->provincia_id)->get() : collect();
        $parroquias = $user->canton_id ? Parroquia::where('canton_id', $user->canton_id)->get() : collect();
        
        return view('users.edit', compact('user', 'provincias', 'cantones', 'parroquias'));
    }

    public function update(Request $request, User $user)
    {
        if (!Auth::user()->esAdminGeneral()) {
            abort(403, 'No tienes permisos para actualizar usuarios.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'role' => 'required|in:admin,admin_provincial,admin_cantonal,admin_parroquial',
            'password' => 'nullable|string|min:8|confirmed', // Opcional
            'provincia_id' => 'nullable|exists:provincias,id',
            'canton_id' => 'nullable|exists:cantones,id',
            'parroquia_id' => 'nullable|exists:parroquias,id',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'dignidad_asignada' => $request->dignidad_asignada ?? 'todas',
            'provincia_id' => $request->provincia_id,
            'canton_id' => $request->canton_id,
            'parroquia_id' => $request->parroquia_id,
        ];

        // Si el Súper Admin escribió una nueva contraseña, se procesa. Si no, se ignora y se mantiene la actual.
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return redirect()->route('users.index')->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $user)
    {
        if (!Auth::user()->esAdminGeneral()) {
            abort(403, 'No tienes permisos para eliminar usuarios.');
        }

        // Impedir que el Súper Admin se auto-elimine
        if ($user->id === Auth::id()) {
            return redirect()->route('users.index')->with('error', 'No puedes eliminar tu propia cuenta de súper administrador.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Usuario eliminado del sistema correctamente.');
    }
}