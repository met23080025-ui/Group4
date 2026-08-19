<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Gym;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Quản lý Gym cấp platform (mục 1, Ngày 3) — chỉ Platform Admin chạm tới,
 * đã bị khóa hoàn toàn bởi middleware `role:platform_admin` ở route
 * `/admin/*` (xem routes/web.php), nên không cần thêm Policy riêng — cùng
 * mức bảo vệ như route `admin.dashboard` gốc từ Ngày 1.
 */
class GymController extends Controller
{
    public function index(): View
    {
        $gyms = Gym::query()
            ->withCount(['users', 'members'])
            ->orderBy('name')
            ->paginate(15);

        return view('admin.gyms.index', ['gyms' => $gyms]);
    }

    public function toggleActive(Gym $gym): RedirectResponse
    {
        $gym->update(['is_active' => ! $gym->is_active]);

        return redirect()
            ->route('admin.gyms.index')
            ->with('success', $gym->is_active ? "Đã kích hoạt {$gym->name}." : "Đã vô hiệu hóa {$gym->name}.");
    }
}
