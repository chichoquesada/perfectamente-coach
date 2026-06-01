<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Si está ON, los checks de suplementos cuentan en el % de fidelidad
            // junto con las comidas. Default OFF (comportamiento previo).
            $table->boolean('supplements_affect_fidelity')
                ->default(false)
                ->after('gamification_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('supplements_affect_fidelity');
        });
    }
};
