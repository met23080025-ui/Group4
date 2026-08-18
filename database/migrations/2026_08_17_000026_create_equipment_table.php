<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained('gyms')->cascadeOnDelete();
            $table->string('name');
            $table->string('category')->nullable();
            $table->date('purchase_date')->nullable();
            $table->string('status')->default('active');
            $table->unsignedInteger('maintenance_interval_days')->nullable();
            $table->date('last_maintenance_at')->nullable();
            $table->date('next_maintenance_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['gym_id', 'status']);
            $table->index(['gym_id', 'next_maintenance_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment');
    }
};
