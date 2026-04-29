<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('nombre')->nullable();
            $table->enum('plan_tier', ['free', 'pro'])->default('free');
            $table->string('stripe_customer_id')->nullable()->unique();
            $table->string('stripe_subscription_id')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->string('affiliate_code')->nullable()->unique();
            $table->string('referred_by_code')->nullable();
            $table->json('calendario_entreno')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
