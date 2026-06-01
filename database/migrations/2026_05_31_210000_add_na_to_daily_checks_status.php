<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Agrega 'na' (no aplica) al enum de status. Permite marcar un ítem que
        // ese día no correspondía (ej: suplemento día por medio): no cuenta ni a
        // favor ni en contra de la fidelidad.
        DB::statement("ALTER TABLE daily_checks MODIFY status ENUM('fiel', 'parcial', 'nofiel', 'na') NOT NULL");
    }

    public function down(): void
    {
        // Revertir: cualquier 'na' existente pasa a 'nofiel' para no violar el enum.
        DB::statement("UPDATE daily_checks SET status = 'nofiel' WHERE status = 'na'");
        DB::statement("ALTER TABLE daily_checks MODIFY status ENUM('fiel', 'parcial', 'nofiel') NOT NULL");
    }
};
