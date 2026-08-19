<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            // PT phụ trách chính của hội viên (Khối 6) — 1 member có tối đa 1
            // trainer phụ trách tại 1 thời điểm (đủ cho phạm vi đề bài, không
            // cần lưu lịch sử đổi PT). Gán/gỡ qua thao tác cập nhật đơn giản,
            // KHÔNG dùng bảng pivot vì không cần nhiều-nhiều hay lịch sử.
            $table->foreignId('trainer_id')->nullable()->after('gym_id')
                ->constrained('trainers')->nullOnDelete();

            $table->index(['gym_id', 'trainer_id']);
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropConstrainedForeignId('trainer_id');
        });
    }
};
