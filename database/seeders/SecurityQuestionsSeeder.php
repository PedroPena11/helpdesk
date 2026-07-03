<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SecurityQuestionsSeeder extends Seeder
{
    public function run(): void
    {
        $questions = [
            ['question' => '¿Cuál fue el nombre de tu primera mascota?'],
            ['question' => '¿En qué ciudad se conocieron tus padres?'],
            ['question' => '¿Cuál es el nombre de tu libro favorito?'],
            ['question' => '¿Cuál fue el modelo de tu primer carro o vehículo?'],
            ['question' => '¿Cómo se llamaba tu escuela primaria?'],
        ];

        DB::table('security_questions')->insert($questions);
    }
}
