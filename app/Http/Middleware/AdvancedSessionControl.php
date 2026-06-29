<?php

namespace App\Http\Middleware;

use App\Models\Auditoria;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class AdvancedSessionControl
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('api/login')) {
        return $next($request);
    }

        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();
        $currentToken = hash('sha256', $request->bearerToken());
        $now = now();

        
    $sessionRecord = DB::table('user_sessions')
        ->where('user_id', $user->id)
        ->first();

    if ($sessionRecord) {
        
        if ($sessionRecord->session_token !== $currentToken) {
            
            
            $user->currentAccessToken()->delete();

            Auditoria::registrar($user->id, 'SESION_CONCURRENTE', "Se bloqueó un intento de acceso concurrente.");
            
            return response()->json([
                'error' => 'session_concurrent',
                'message' => 'Se ha iniciado sesión desde otro dispositivo. Esta sesión fue cerrada por seguridad.'
            ], 401);
        }

        
        $lastActivity = \Carbon\Carbon::parse($sessionRecord->last_activity);
        $inactivityLimit = 2; // minutos

        if ($lastActivity->diffInMinutes($now) > $inactivityLimit || $now->greaterThan($sessionRecord->expires_at)) {
            
            
            DB::table('user_sessions')->where('user_id', $user->id)->delete();
            $user->currentAccessToken()->delete();

            Auditoria::registrar($user->id, 'SESION_FINALIZADA_PO_INACTIVIDAD', "Sesion finalizada por inactividad.");

            return response()->json([
                'error' => 'session_expired',
                'message' => 'Tu sesión ha expirado por inactividad. Por favor, inicia sesión de nuevo.'
            ], 401);
        }

        
        DB::table('user_sessions')
            ->where('user_id', $user->id)
            ->update([
                'last_activity' => $now,
                'ip_address' => $request->ip(),
                'updated_at' => $now
            ]);
}else {
           
            DB::table('user_sessions')->insert([
                'user_id' => $user->id,
                'session_token' => $currentToken,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'last_activity' => $now,
                'expires_at' => $now->addHours(2),
                'created_at' => $now,
                'updated_at' => $now
            ]);
        }

        return $next($request);
    }
}