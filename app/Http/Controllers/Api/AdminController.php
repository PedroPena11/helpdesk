<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function storeUser(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,agent,client', 
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        return response()->json([
            'message' => 'Usuario creado exitosamente con el rol de: ' . $user->role,
            'user' => $user
        ], 201);
    }

    public function assignTicket(Request $request, $id)
{
    $request->validate([
        'technician_id' => 'required|exists:users,id',
    ]);

    
    $ticket = Ticket::findOrFail($id);

    
    $technician = User::find($request->technician_id);
    if ($technician->role === 'client') {
        return response()->json([
            'message' => 'Operación inválida. No se puede asignar un ticket a un usuario con rol de cliente.'
        ], 422);
    }

    
    $ticket->technician_id = $request->technician_id;
    
    
    if ($ticket->status === 'abierto') {
        $ticket->status = 'en_progreso';
    }
    
    $ticket->save();

    return response()->json([
        'message' => 'Ticket asignado correctamente al técnico: ' . $technician->name,
        'ticket' => $ticket
    ], 200);
}
}