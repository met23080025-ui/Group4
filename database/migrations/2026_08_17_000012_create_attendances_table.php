<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained('gyms')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('trainer_id')->nullable()->constrained('trainers')->nullOnDelete();
            $table->foreignId('class_booking_id')->nullable()->constrained('class_bookings')->nullOnDelete();
            $table->date('check_in_date');
            $table->timestamp('check_in_time');
            $table->string('source')->default('qr');
            $table->timestamps();

            $table->unique(['gym_id', 'member_id', 'check_in_date']);
            $table->index(['gym_id', 'check_in_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
