<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('nutritional_plan_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->string('item_id');
            $table->enum('status', ['fiel', 'parcial', 'nofiel']);
            $table->text('note')->nullable();
            $table->enum('mode', ['descanso', 'entreno', 'competencia'])->default('descanso');
            $table->timestamps();
            $table->unique(['user_id', 'date', 'item_id']);
            $table->index(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_checks');
    }
};
