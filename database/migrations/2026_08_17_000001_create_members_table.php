<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained('gyms')->cascadeOnDelete();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('member_code');
            $table->date('date_of_birth')->nullable();
            $table->string('gender')->nullable();
            $table->string('address')->nullable();
            $table->string('emergency_contact')->nullable();
            $table->decimal('height', 5, 2)->nullable()->comment('cm');
            $table->decimal('weight', 5, 2)->nullable()->comment('kg');
            $table->string('status')->default('active');
            $table->date('joined_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['gym_id', 'member_code']);
            $table->index(['gym_id', 'status']);
            $table->index(['gym_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
