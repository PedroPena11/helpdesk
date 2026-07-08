<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\PreguntaSeguridad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

   class PreguntaSeguridadController extends Controller{
   
   public function getQuestions()
    {
        try {
            $questions = DB::table('security_questions')
                ->select('id', 'question')
                ->get();


            return response()->json($questions, 200);
        } catch (\Exception $e) {

            return response()->json([
                'message' => 'Error al recuperar las preguntas de seguridad.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

   public function store(Request $request)
    {
        $request->validate([
            'question' => 'required|string|max:255|unique:security_questions,question'
        ]);

        $pregunta = PreguntaSeguridad::create([
            'question' => $request->question
        ]);

        Auditoria::registrar(auth()->id(), 'PREGUNTA_CREADA', "Se creó la pregunta de seguridad: ID {$pregunta->id}");

        return response()->json(['message' => 'Pregunta agregada con éxito.', 'data' => $pregunta], 201);
    }
    
    public function update(Request $request, $id)
    {
        $pregunta = PreguntaSeguridad::findOrFail($id);

        $request->validate([
            'question' => 'required|string|max:255|unique:security_questions,question,' . $id
        ]);

        $pregunta->update($request->only(['question']));

        Auditoria::registrar(auth()->id(), 'PREGUNTA_MODIFICADA', "Se modificó la pregunta de seguridad ID {$id}");

        return response()->json(['message' => 'Pregunta actualizada con éxito.']);
    }

    public function destroy($id)
    {
        $pregunta = PreguntaSeguridad::findOrFail($id);
        
        $pregunta->delete();

        Auditoria::registrar(auth()->id(), 'PREGUNTA_ELIMINADA', "Se eliminó la pregunta de seguridad ID {$id}");

        return response()->json(['message' => 'Pregunta eliminada con éxito.']);
    }
}
