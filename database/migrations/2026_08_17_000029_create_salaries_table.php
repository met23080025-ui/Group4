<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained('gyms')->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->date('month');
            $table->decimal('base_salary', 12, 2);
            $table->decimal('bonus', 12, 2)->default(0);
            $table->decimal('deduction', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->timestamp('paid_at')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['staff_id', 'month']);
            $table->index(['gym_id', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salaries');
    }
};
