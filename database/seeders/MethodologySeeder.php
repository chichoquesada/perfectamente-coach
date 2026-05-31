<?php

namespace Database\Seeders;

use App\Models\Methodology;
use Illuminate\Database\Seeder;

class MethodologySeeder extends Seeder
{
    /**
     * Metodologías base (globales, user_id = null) disponibles para todos
     * los nutricionistas. Idempotente: se puede re-correr sin duplicar.
     */
    public function run(): void
    {
        $base = [
            'Conteo de macros',
            'Sistema de intercambios',
            'Porciones por mano',
            'Plato balanceado',
            'Ayuno intermitente',
            'Carga/descarga de carbohidratos',
        ];

        foreach ($base as $name) {
            Methodology::updateOrCreate(
                ['user_id' => null, 'name' => $name],
                [],
            );
        }
    }
}
