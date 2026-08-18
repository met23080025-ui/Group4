<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mã ngắn của Gym (vd "FZ"), dùng làm tiền tố sinh member_code tự động
     * (Khối 7). Nullable: các test cũ (Khối 3/5/6) tạo Gym trực tiếp không set
     * cột này và không đụng tới luồng sinh member_code nên không bị ảnh hưởng;
     * MemberService sẽ báo lỗi rõ ràng nếu tạo hội viên cho Gym chưa có code.
     */
    public function up(): void
    {
        Schema::table('gyms', function (Blueprint $table) {
            $table->string('code', 10)->nullable()->unique()->after('slug');
        });
    }

    public function down(): void
    {
        Schema::table('gyms', function (Blueprint $table) {
            $table->dropColumn('code');
        });
    }
};
