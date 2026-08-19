<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Trigger "membership sắp hết hạn" (mục 2, Ngày 3) — chạy mỗi ngày 1 lần.
Schedule::command('notifications:expiring-memberships')->daily();
