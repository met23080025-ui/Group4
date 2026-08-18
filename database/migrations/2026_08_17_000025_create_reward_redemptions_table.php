<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reward_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained('gyms')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('reward_id')->constrained('rewards')->restrictOnDelete();
            $table->unsignedInteger('points_spent');
            $table->string('status')->default('pending');
            $table->timestamp('redeemed_at')->nullable();
            $table->timestamps();

            $table->index(['gym_id', 'member_id']);
            $table->index('reward_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reward_redemptions');
    }
};
