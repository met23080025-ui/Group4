<?php

namespace App\Http\Middleware;

use App\Models\Gym;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Xác định Gym hiện tại của user đăng nhập và share ra mọi view ($currentGym)
 * để dùng cho branding sidebar/topbar. KHÔNG chịu trách nhiệm chặn quyền truy
 * cập — việc đó do RoleMiddleware và Policy đảm nhiệm.
 */
class EnsureTenantAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // platform_admin không gắn với gym cụ thể nào -> $currentGym = null, không crash.
        $currentGym = $user?->gym_id ? Gym::find($user->gym_id) : null;

        View::share('currentGym', $currentGym);

        return $next($request);
    }
}
