<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Dùng: ->middleware('role:gym_owner,staff')
 * Sai role -> 403 Forbidden (KHÔNG redirect về login, vì user đã đăng nhập
 * hợp lệ, chỉ là không đủ quyền cho route này).
 */
class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, $roles, true)) {
            abort(403, 'Bạn không có quyền truy cập chức năng này.');
        }

        return $next($request);
    }
}
