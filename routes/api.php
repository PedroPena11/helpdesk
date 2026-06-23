<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TicketController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AdminController;
use App\Http\Middleware\AdvancedSessionControl;

Route::post('/login', [AuthController::class, 'login']);
Route::get('/security-questions', [AuthController::class, 'getQuestions']);
Route::post('/forgot-password', [AuthController::class, 'sendResetLinkEmail']);
Route::post('/reset-password', [AuthController::class, 'resetPasswordByEmail']);
Route::post('/recovery/get-question', [AuthController::class, 'getRecoveryQuestion']);
Route::post('/recovery/reset-password', [AuthController::class, 'resetPasswordByQuestion']);


Route::middleware(['auth:sanctum', AdvancedSessionControl::class])->group(function () {
    Route::apiResource('tickets', TicketController::class);
    Route::get('/tickets', [TicketController::class, 'index']);
    Route::post('/tickets/{id}/claim', [TicketController::class, 'claimTicket']);
    Route::post('/tickets', [TicketController::class, 'store']);
    Route::put('/tickets/{id}/complete', [TicketController::class, 'complete']);
    Route::post('/user/change-password', [AuthController::class, 'updatePassword']);
    Route::post('/user/update-security', [AuthController::class, 'updateSecuritySettings']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/user/security-setup', [AuthController::class, 'saveSecurityQuestions']);
    Route::middleware('role:admin')->group(function () {
        Route::post('/admin/users', [AdminController::class, 'storeUser']);
        Route::get('/admin/technicians', [AdminController::class, 'getTechnicians']);
        Route::post('/admin/tickets/{ticket}/assign', [AdminController::class, 'assignTicket']);
        Route::get('/admin/users', [AdminController::class, 'getAllUsers']);
        Route::post('/admin/users', [AdminController::class, 'storeUser']); // Mapea al que ya tenías
        Route::delete('/admin/users/{id}', [AdminController::class, 'deleteUser']);
    });
});
