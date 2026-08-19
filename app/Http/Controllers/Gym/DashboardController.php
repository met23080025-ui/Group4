<?php

namespace App\Http\Controllers\Gym;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(DashboardService $dashboardService): View
    {
        return view('gym.dashboard', $dashboardService->ownerOverview());
    }
}
