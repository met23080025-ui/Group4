<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained('gyms')->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('discount_type')->default('percent');
            $table->decimal('discount_value', 12, 2);
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['gym_id', 'code']);
            $table->index(['gym_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
