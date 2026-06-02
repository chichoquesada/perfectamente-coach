<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // % de fidelidad mínimo para que un día "cuente" en la racha. Cada
            // paciente elige su meta (más exigente o más alcanzable). Default 60.
            $table->unsignedTinyInteger('streak_threshold')
                ->default(60)
                ->after('supplements_affect_fidelity');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('streak_threshold');
        });
    }
};
