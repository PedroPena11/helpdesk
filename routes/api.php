<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TicketController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AdminController;


Route::post('/login',[AuthController::class,'login']);

Route::middleware('auth:sanctum')->group(function(){
    Route::apiResource('tickets',TicketController::class);
    Route::get('/tickets', [TicketController::class, 'index']);
    Route::post('/tickets/{id}/claim',[TicketController::class,'claimTicket']);
    Route::post('/tickets', [TicketController::class, 'store']);
    Route::put('/tickets/{id}/complete', [TicketController::class, 'complete']);
    Route::post('/logout',[AuthController::class,'logout']);
    Route::middleware('role:admin')->group(function(){
        Route::post('/admin/users',[AdminController::class,'storeUser']);
        Route::get('/admin/technicians',[AdminController::class,'getTechnicians']);
        Route::post('/admin/tickets/{ticket}/assign',[AdminController::class,'assignTicket']);
    });
});
