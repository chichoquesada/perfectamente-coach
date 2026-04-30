<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nutritionist_patient', function (Blueprint $table) {
            $table->foreignId('nutritionist_id')->after('id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('patient_id')->nullable()->after('nutritionist_id')->constrained('users')->nullOnDelete();
            $table->enum('status', ['invited', 'active', 'archived'])->default('invited')->after('patient_id');
            $table->string('invitation_token', 64)->nullable()->unique()->after('status');
            $table->string('invitation_email')->nullable()->after('invitation_token');
            $table->timestamp('invited_at')->nullable()->after('invitation_email');
            $table->timestamp('accepted_at')->nullable()->after('invited_at');
            $table->timestamp('archived_at')->nullable()->after('accepted_at');

            $table->index(['nutritionist_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('nutritionist_patient', function (Blueprint $table) {
            $table->dropForeign(['nutritionist_id']);
            $table->dropForeign(['patient_id']);
            $table->dropIndex(['nutritionist_id', 'status']);
            $table->dropColumn([
                'nutritionist_id', 'patient_id', 'status',
                'invitation_token', 'invitation_email',
                'invited_at', 'accepted_at', 'archived_at',
            ]);
        });
    }
};
