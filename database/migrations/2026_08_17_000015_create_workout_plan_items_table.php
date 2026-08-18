<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workout_plan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_plan_id')->constrained('workout_plans')->cascadeOnDelete();
            $table->string('exercise_name');
            $table->unsignedInteger('sets')->nullable();
            $table->unsignedInteger('reps')->nullable();
            $table->decimal('weight', 6, 2)->nullable();
            $table->unsignedInteger('rest_seconds')->nullable();
            $table->string('day_of_week')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('workout_plan_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workout_plan_items');
    }
};
