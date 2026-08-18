<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            // Đánh dấu buổi tập 1-kèm-1 với PT (thường capacity=1) khác với lớp
            // nhóm — chỉ buổi is_pt_session=true mới trừ remaining_pt_sessions
            // của membership khi member đặt chỗ (Khối 4).
            $table->boolean('is_pt_session')->default(false)->after('capacity');
        });

        Schema::table('class_bookings', function (Blueprint $table) {
            // Ghi lại đúng membership đã được dùng để đặt chỗ — cần thiết để
            // hoàn (refund) đúng remaining_pt_sessions khi huỷ booking một buổi
            // PT, kể cả khi member đã có membership mới khác vào lúc huỷ.
            $table->foreignId('membership_id')->nullable()->after('member_id')
                ->constrained('memberships')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('class_bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('membership_id');
        });

        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn('is_pt_session');
        });
    }
};
