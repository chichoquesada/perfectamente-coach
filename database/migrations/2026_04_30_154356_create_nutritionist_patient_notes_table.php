<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nutritionist_patient_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nutritionist_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();

            $table->index(['nutritionist_id', 'patient_id', 'created_at'], 'np_notes_nutri_patient_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nutritionist_patient_notes');
    }
};
