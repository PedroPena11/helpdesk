<?php

namespace App\Http\Controllers\Api;

use App\Events\TicketCompleted;
use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;
use App\Events\TicketCreated;
use Illuminate\Support\Facades\Auth;

class TicketController extends Controller
{
    /**
     * Mostrar la lista de clientes
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Ticket::with(['client','technician'])->orderBy('created_at','desc');

        if($user->role === 'agent'){
            $query->where('technician_id', $user->id);
        }

        if ($user->role === 'client') { $query->where('client_id', $user->id); }

        $tickets = $query->get();

        return response()->json($tickets,200);
    }

    /**
     * Crear nuevo ticket en la base de datos
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|in:baja,media,alta,critica',
        ]);

        $ticket = Ticket::create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'priority' => $validated['priority'],
            'client_id' => $request->user()->id,
            'status' => 'abierto',
        ]);

        $ticket->load('client');

        broadcast(new TicketCreated($ticket))->toOthers();

        return response()->json([
            'message' => 'Ticket creado exitosamente',
            'ticket' => $ticket
        ],201);

    }

    public function complete(Request $request, $id){
        $user = $request->user();
        $ticket = Ticket::findOrFail($id);

        if ($user->role === 'agent' && $ticket->technician_id !== $user->id) {
        return response()->json([
            'message' => 'No autorizado. Este ticket no está asignado a ti.'
        ], 403);
    }

    $ticket->status = 'resuelto';
    $ticket->resolved_at = now();
    $ticket->save();

    $ticket->load(['client', 'technician']);

    broadcast(new TicketCompleted($ticket))->toOthers();

    return response()->json([
        'message' => '¡Excelente! El ticket ha sido marcado como completado.',
        'ticket' => $ticket
    ], 200);


    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
