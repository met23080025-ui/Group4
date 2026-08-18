<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained('gyms')->cascadeOnDelete();
            $table->foreignId('payment_id')->unique()->constrained('payments')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->string('invoice_number')->unique();
            $table->timestamp('issued_at');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->string('pdf_path')->nullable();
            $table->timestamps();

            $table->index(['gym_id', 'issued_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
