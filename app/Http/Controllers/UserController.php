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
        
        if (!$auth->esAdmin() && !$auth->esAdminProvincial() && !$auth->esAdminCantonal()) {
            abort(403, 'No tienes permisos para gestionar usuarios.');
        }

        $query = User::query();

        if ($auth->esAdminGeneral()) {
            $users = $query->with(['provincia', 'canton', 'parroquia'])->get();
        } 
        elseif ($auth->esAdminProvincial()) {
            $users = $query->where('provincia_id', $auth->provincia_id)
                        ->whereNotIn('role', ['admin', 'admin_provincial']) 
                        ->with(['canton', 'parroquia'])
                        ->get();
        } 
        elseif ($auth->esAdminCantonal()) {
            $users = $query->where('canton_id', $auth->canton_id)
                        ->whereNotIn('role', ['admin', 'admin_provincial', 'admin_cantonal'])
                        ->with(['parroquia'])
                        ->get();
        }

        return view('users.index', compact('users'));
    }

    public function create()
    {
        $auth = Auth::user();
        $provincias = collect(); 
        $cantones = collect();
        $parroquias = collect();

        if ($auth->esAdminGeneral()) {
            $provincias = Provincia::all();
        } elseif ($auth->esAdminProvincial()) {
            $cantones = Canton::where('provincia_id', $auth->provincia_id)->get();
        } elseif ($auth->esAdminCantonal()) {
            $parroquias = Parroquia::where('canton_id', $auth->canton_id)->get();
        }

        return view('users.create', compact('provincias', 'cantones', 'parroquias'));
    }

    public function store(Request $request)
    {
        $auth = Auth::user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required',
            'provincia_id' => 'nullable|exists:provincias,id',
            'canton_id' => 'nullable|exists:cantones,id',
            'parroquia_id' => 'nullable|exists:parroquias,id',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'dignidad_asignada' => $request->dignidad_asignada ?? 'N/A',
        ];

        if ($auth->esAdminGeneral()) {
            $data['provincia_id'] = $request->provincia_id;
            $data['canton_id'] = $request->canton_id;
            $data['parroquia_id'] = $request->parroquia_id;
        } 
        elseif ($auth->esAdminProvincial()) {
            $data['provincia_id'] = $auth->provincia_id; 
            $data['canton_id'] = $request->canton_id;
            $data['parroquia_id'] = $request->parroquia_id;
        } 
        elseif ($auth->esAdminCantonal()) {
            $data['provincia_id'] = $auth->provincia_id;
            $data['canton_id'] = $auth->canton_id;
            $data['parroquia_id'] = $request->parroquia_id;
        }

        User::create($data);
        return redirect()->route('users.index')->with('success', 'Usuario registrado con éxito.');
    }

    public function edit(User $user)
    {
        $auth = Auth::user();
        
        if (!$auth->esAdminGeneral()) {
            if ($auth->esAdminProvincial() && $user->provincia_id !== $auth->provincia_id) abort(403);
            if ($auth->esAdminCantonal() && $user->canton_id !== $auth->canton_id) abort(403);
        }

        // CORRECCIÓN: Carga filtrada de territorios para el formulario de edición
        $provincias = $auth->esAdminGeneral() ? Provincia::all() : collect();
        $cantones = $auth->esAdminGeneral() ? Canton::all() : Canton::where('provincia_id', $auth->provincia_id)->get();
        $parroquias = $auth->esAdminCantonal() ? Parroquia::where('canton_id', $auth->canton_id)->get() : Parroquia::all();
        
        return view('users.edit', compact('user', 'provincias', 'cantones', 'parroquias'));
    }

    public function update(Request $request, User $user)
    {
        $auth = Auth::user();

        $request->validate([
            'role' => 'required',
            'dignidad_asignada' => 'required',
        ]);

        $data = $request->only(['role', 'dignidad_asignada', 'canton_id', 'parroquia_id']);
        
        if (!$auth->esAdminGeneral()) {
            // Impedimos que cambien la provincia si no son SuperAdmin
            $data['provincia_id'] = $auth->provincia_id; 
            
            if ($auth->esAdminCantonal()) {
                $data['canton_id'] = $auth->canton_id;
            }
        }

        $user->update($data);
        return redirect()->route('users.index')->with('success', 'Usuario actualizado correctamente.');
    }
}