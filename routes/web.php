<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/password/reset/{token}', function (Request $request, $token) {
    return view('reset-password-isolated', [
        'token' => $token,
        'email' => $request->query('email')
    ]);
})->name('password.reset');


Route::get('/{any}',function(){
    return view('welcome');
})->where('any',".*");