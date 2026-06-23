<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\PasswordReset;

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
            'security_question_id' => 'required|exists:security_question,id',
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

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'La contraseña actual no es correcta.'
            ], 422);
        }


        DB::table('users')->where('id', $user->id)->update([
            'password' => Hash::make($request->new_password),
            'updated_at' => now()
        ]);

        return response()->json([
            'message' => 'Contraseña actualizada con éxito con los estándares de seguridad.'
        ], 200);
    }

    public function updateSecuritySettings(Request $request)
    {
        $request->validate([
            'security_question_id' => 'required|exists:security_questions,id',
            'answer' => 'required|string|min:3|max:255',
            'password_verification' => 'required'
        ]);

        $user = $request->user();


        if (!Hash::check($request->password_verification, $user->password)) {
            return response()->json([
                'message' => 'Validación de identidad fallida. Contraseña incorrecta.'
            ], 422);
        }

        DB::beginTransaction();
        try {

            DB::table('user_security_answers')->where('user_id', $user->id)->delete();


            DB::table('user_security_answers')->insert([
                'user_id' => $user->id,
                'security_question_id' => $request->security_question_id,
                'answer' => Hash::make(strtolower(trim($request->answer))),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();
            return response()->json(['message' => 'Opciones de recuperación actualizadas con éxito.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error interno en el servidor.'], 500);
        }
    }

    public function getAllUsers(Request $request)
    {

        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Acceso denegado.'], 403);
        }

        $users = DB::table('users')
            ->select('id', 'name', 'email', 'role', 'setup_completed', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($users, 200);
    }

    public function storeUser(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Acceso denegado.'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'role' => 'required|in:admin,agent,client',
            'password' => 'required|string|min:8'
        ]);

        DB::table('users')->insert([
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'password' => Hash::make($request->password),
            'setup_completed' => false,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json(['message' => 'Usuario registrado exitosamente.'], 201);
    }

    public function deleteUser(Request $request, $id)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Acceso denegado.'], 403);
        }


        if ($request->user()->id == $id) {
            return response()->json(['message' => 'No puedes eliminar tu propia cuenta.'], 400);
        }

        DB::beginTransaction();
        try {

            DB::table('user_security_answers')->where('user_id', $id)->delete();
            DB::table('users')->where('id', $id)->delete();

            DB::commit();
            return response()->json(['message' => 'Usuario eliminado correctamente de los registros.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al eliminar el usuario.'], 500);
        }
    }



    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        $status = Password::broker()->sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'message' => 'Te hemos enviado un enlace de recuperación a tu correo electrónico.'
            ], 200);
        }

        return response()->json([
            'message' => 'No se pudo enviar el correo de recuperación.'
        ], 422);
    }


    public function resetPasswordByEmail(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);


        $status = Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {

                $user->password = Hash::make($password);
                $user->setRememberToken(Str::random(60));
                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'message' => 'Tu contraseña ha sido reestablecida con éxito en PostgreSQL. Ya puedes iniciar sesión.'
            ], 200);
        }

        return response()->json([
            'message' => __($status)
        ], 422);
    }

    public function getRecoveryQuestion(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ]);


        $user = DB::table('users')->where('email', $request->email)->first();


        $userAnswer = DB::table('user_security_answers')
            ->where('user_id', $user->id)
            ->first();

        if (!$userAnswer) {
            return response()->json([
                'message' => 'Este usuario no tiene configuradas preguntas de seguridad de recuperación.'
            ], 422);
        }


        $questionText = DB::table('security_questions')
            ->where('id', $userAnswer->security_question_id)
            ->value('question');

        return response()->json([
            'email' => $user->email,
            'question' => $questionText
        ], 200);
    }


    public function resetPasswordByQuestion(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'answer' => 'required|string',
            'password' => 'required|string|min:8|confirmed'
        ]);

        $user = DB::table('users')->where('email', $request->email)->first();

        $userAnswer = DB::table('user_security_answers')
            ->where('user_id', $user->id)
            ->first();


        $processedAnswer = strtolower(trim($request->answer));

        if (!Hash::check($processedAnswer, $userAnswer->answer)) {
            return response()->json([
                'message' => 'La respuesta de seguridad es incorrecta. Validación fallida.'
            ], 422);
        }

        DB::table('users')->where('id', $user->id)->update([
            'password' => Hash::make($request->password),
            'updated_at' => now()
        ]);

        return response()->json([
            'message' => 'Contraseña reestablecida con éxito. Ya puedes iniciar sesión.'
        ], 200);
    }
}
