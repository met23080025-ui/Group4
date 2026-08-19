<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;

/**
 * Trigger "membership sắp hết hạn" (mục 2, Ngày 3). Chạy thủ công cho demo
 * (`php artisan notifications:expiring-memberships`) — lịch chạy tự động
 * hàng ngày đã khai báo ở `routes/console.php`.
 */
class NotifyExpiringMemberships extends Command
{
    protected $signature = 'notifications:expiring-memberships {--days=3 : Số ngày trước khi hết hạn để bắt đầu cảnh báo}';

    protected $description = 'Thông báo cho member có membership sắp hết hạn trong N ngày tới';

    public function handle(NotificationService $notificationService): int
    {
        $days = (int) $this->option('days');

        $count = $notificationService->notifyExpiringMemberships($days);

        $this->info("Đã gửi {$count} thông báo membership sắp hết hạn (trong {$days} ngày tới).");

        return self::SUCCESS;
    }
}
