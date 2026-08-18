<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained('gyms')->cascadeOnDelete();
            $table->foreignId('equipment_id')->constrained('equipment')->cascadeOnDelete();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('performed_at');
            $table->text('description')->nullable();
            $table->decimal('cost', 12, 2)->nullable();
            $table->timestamps();

            $table->index(['gym_id', 'equipment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_records');
    }
};
