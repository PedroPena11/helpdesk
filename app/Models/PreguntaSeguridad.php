<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PreguntaSeguridad extends Model
{
    use HasFactory;

    protected $table = 'security_questions'; 

    protected $fillable = [
        'question'
    ];
}