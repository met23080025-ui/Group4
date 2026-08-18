<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained('gyms')->cascadeOnDelete();
            $table->foreignId('trainer_id')->nullable()->constrained('trainers')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('class_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedInteger('capacity');
            $table->string('status')->default('scheduled');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['gym_id', 'class_date']);
            $table->index('trainer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
