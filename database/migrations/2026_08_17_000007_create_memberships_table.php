<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained('gyms')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('package_id')->constrained('packages')->restrictOnDelete();
            $table->foreignId('promotion_id')->nullable()->constrained('promotions')->nullOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('original_price', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('final_price', 12, 2);
            $table->unsignedInteger('remaining_pt_sessions')->default(0);
            $table->string('status')->default('pending');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['gym_id', 'status']);
            $table->index(['gym_id', 'end_date']);
            $table->index('member_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memberships');
    }
};
