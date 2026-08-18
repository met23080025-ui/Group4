<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('package_promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained('packages')->cascadeOnDelete();
            $table->foreignId('promotion_id')->constrained('promotions')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['package_id', 'promotion_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_promotions');
    }
};
