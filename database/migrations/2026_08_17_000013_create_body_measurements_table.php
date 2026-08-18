<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('body_measurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained('gyms')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('measured_at');
            $table->decimal('height', 5, 2)->nullable();
            $table->decimal('weight', 5, 2)->nullable();
            $table->decimal('body_fat_percent', 5, 2)->nullable();
            $table->decimal('muscle_mass', 5, 2)->nullable();
            $table->decimal('bmi', 5, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['gym_id', 'member_id', 'measured_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('body_measurements');
    }
};
