<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;
use App\Events\TicketCreated;

class TicketController extends Controller
{
    /**
     * Mostrar la lista de clientes
     */
    public function index()
    {
        $tickets = Ticket::with(['client','technician'])->latest()->get();

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
            'client_id' => 1, //Id de Prueba
        ]);

        broadcast(new TicketCreated($ticket))->toOthers();

        return response()->json([
            'message' => 'Ticket creado exitosamente',
            'ticket' => $ticket
        ],201);

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
