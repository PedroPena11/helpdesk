<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Auditoria extends Model
{
    protected $fillable = ['user_id', 'operacion', 'detalles', 'ip_address', 'user_agent'];

    public function user()
    {
        return $this->belongsTo(User::class)->select('id', 'name', 'email');
    }

    public static function registrar($userId, $operacion, $detalles)
{
    self::create([
        'user_id' => $userId,
        'operacion' => $operacion,
        'detalles' => $detalles,
        'ip_address' => request()->ip(),
        'user_agent' => request()->userAgent(),
    ]);
}
}