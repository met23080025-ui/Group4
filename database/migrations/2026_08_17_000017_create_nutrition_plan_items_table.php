<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nutrition_plan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nutrition_plan_id')->constrained('nutrition_plans')->cascadeOnDelete();
            $table->string('meal_name');
            $table->time('meal_time')->nullable();
            $table->string('food');
            $table->decimal('calories', 8, 2)->nullable();
            $table->decimal('protein', 8, 2)->nullable();
            $table->decimal('carbs', 8, 2)->nullable();
            $table->decimal('fat', 8, 2)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('nutrition_plan_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nutrition_plan_items');
    }
};
