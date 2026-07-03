<?php

namespace App\Http\Controllers\Api;

use App\Events\TicketAssigned;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function getAllUsers(Request $request)
    {
        $users = User::select('id', 'name', 'email', 'role', 'setup_completed', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($users, 200);
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

        $ticket->load(['client', 'technician']);

        broadcast(new TicketAssigned($ticket))->toOthers();


        return response()->json([
            'message' => 'Ticket asignado correctamente al técnico: ' . $technician->name,
            'ticket' => $ticket
        ], 200);
    }

    public function getTechnicians()
    {
        $technicians = User::where('role', 'agent')
            ->select('id', 'name', 'email')
            ->get();

        return response()->json($technicians, 200);
    }

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
            'setup_completed' => false,
        ]);

        return response()->json([
            'message' => 'Usuario creado exitosamente en el sistema.',
            'user' => $user
        ], 201);
    }

    public function deleteUser(Request $request, $id)
    {

        if ($request->user()->id == $id) {
            return response()->json([
                'message' => 'Operación denegada. No puedes eliminar tu propio usuario de la sesión activa.'
            ], 400);
        }

        $user = User::findOrFail($id);

        DB::beginTransaction();
        try {

            DB::table('user_security_answers')->where('user_id', $id)->delete();


            $user->delete();

            DB::commit();
            return response()->json([
                'message' => 'Usuario purgado exitosamente del sistema helpdesk.'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error interno al procesar la baja del usuario.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
