<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            // 'key' identifica la medalla (semana_perfecta, racha_30, etc.). El
            // catálogo vive en App\Support\Gamification::medals().
            $table->string('key');
            $table->timestamp('unlocked_at');
            $table->timestamps();
            // Un usuario desbloquea cada medalla una sola vez.
            $table->unique(['user_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_achievements');
    }
};
