<?php

namespace App\Http\Controllers\Api;

use App\Events\TicketAssigned;
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
    /** @var \App\Models\User $user */
    $user = $request->user();

    $query = Ticket::with(['client', 'technician'])->orderBy('created_at', 'desc');

    if ($user->role === 'agent') {
        $query->whereIn('status', ['abierto', 'en_progreso', 'resuelto']);
    } elseif ($user->role === 'client') {
        $query->where('client_id', $user->id);
    }
    $tickets = $query->get();

    return response()->json($tickets, 200);
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


public function claimTicket(Request $request, $id)
{
   
    $ticket = Ticket::findOrFail($id);

    
    if ($ticket->technician_id !== null) {
        return response()->json(['message' => 'Este ticket ya fue tomado por otro técnico.'], 400);
    }

    
    /** @var \App\Models\User $user */
    $user = $request->user(); 

    
    if ($user->role !== 'agent') {
        return response()->json(['message' => 'Solo los técnicos pueden tomar tareas.'], 403);
    }

    
    $ticket->technician_id = $user->id;
    $ticket->status = 'en_progreso';
    $ticket->started_at = now();      
    $ticket->save();

    
    $ticket->load(['client', 'technician']);

    
    broadcast(new \App\Events\TicketUpdated($ticket))->toOthers(); 

    return response()->json($ticket, 200);
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
