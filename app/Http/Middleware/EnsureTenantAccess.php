<?php

namespace App\Http\Middleware;

use App\Models\Gym;
use App\Models\Notification;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Xác định Gym hiện tại của user đăng nhập và share ra mọi view ($currentGym)
 * để dùng cho branding sidebar/topbar. Cũng share dữ liệu chuông thông báo
 * ($unreadNotificationsCount/$recentNotifications, Khối 2 Ngày 3) vì đây là
 * middleware DUY NHẤT đã chạy trên mọi trang đã đăng nhập — tránh phải thêm
 * 1 middleware share-view riêng chỉ cho việc này. KHÔNG chịu trách nhiệm
 * chặn quyền truy cập — việc đó do RoleMiddleware và Policy đảm nhiệm.
 *
 * Share thêm $currentMember (hồ sơ Member của chính user, nếu role là member)
 * để sidebar dựng được link tới measurements/workout-plans/nutrition-plans
 * (các route này cần {member} trong URL) mà không phải query lại ở mỗi view.
 */
class EnsureTenantAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // platform_admin không gắn với gym cụ thể nào -> $currentGym = null, không crash.
        $currentGym = $user?->gym_id ? Gym::find($user->gym_id) : null;

        View::share('currentGym', $currentGym);
        View::share('currentMember', $user?->role === 'member' ? $user->member : null);

        if ($user) {
            $query = Notification::query()->where('user_id', $user->id);

            View::share('unreadNotificationsCount', (clone $query)->whereNull('read_at')->count());
            View::share('recentNotifications', (clone $query)->latest()->limit(5)->get());
        } else {
            View::share('unreadNotificationsCount', 0);
            View::share('recentNotifications', collect());
        }

        return $next($request);
    }
}
