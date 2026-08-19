<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            // Khóa bí mật riêng từng member, dùng để KÝ (HMAC) token QR check-in
            // (Khối 5). Sinh "lười" (lazy) lần đầu member xem QR của mình, KHÔNG
            // sinh sẵn hàng loạt — column nullable. Mã hóa tại rest bằng cast
            // 'encrypted' của Eloquent (dùng APP_KEY) vì đây là khóa bí mật, không
            // phải dữ liệu nghiệp vụ thuần túy như các cột khác của bảng này.
            $table->text('qr_secret')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn('qr_secret');
        });
    }
};
