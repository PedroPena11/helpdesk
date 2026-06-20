<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ["Las credenciales proporcionadas son incorrectas."],
            ]);
        }


        \Illuminate\Support\Facades\DB::table('user_sessions')->where('user_id', $user->id)->delete();

        $token = $user->createToken('auth-token')->plainTextToken;

        \Illuminate\Support\Facades\DB::table('user_sessions')->insert([
            'user_id' => $user->id,
            'session_token' => hash('sha256', $token),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'last_activity' => now(),
            'expires_at' => now()->addHours(2),
            'created_at' => now(),
            'updated_at' => now()
        ]);


        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'setup_completed' => (bool) $user->setup_completed,
            ]
        ], 200);
    }

    public function logout(Request $request)
    {

        $user = $request->user();

        if ($user) {

            \Illuminate\Support\Facades\DB::table('user_sessions')
                ->where('user_id', $user->id)
                ->delete();


            $user->currentAccessToken()->delete();

            return response()->json([
                'message' => 'Sesión cerrada exitosamente y registros de acceso liberados.'
            ], 200);
        }

        return response()->json([
            'message' => 'No hay una sesión activa para cerrar.'
        ], 401);
    }


    public function saveSecurityQuestions(Request $request)
    {
        $request->validate([
            'security_question_id' => 'required|exists:security_questions,id',
            'answer' => 'required|string|min:3|max:255',
        ]);

        $user = $request->user();


        DB::beginTransaction();
        try {

            DB::table('user_security_answers')->insert([
                'user_id' => $user->id,
                'security_question_id' => $request->security_question_id,
                'answer' => Hash::make(strtolower(trim($request->answer))),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('users')->where('id', $user->id)->update([
                'setup_completed' => true
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Pregunta de seguridad registrada con éxito.',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'setup_completed' => true
                ]
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error interno al guardar la configuración.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

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
}
