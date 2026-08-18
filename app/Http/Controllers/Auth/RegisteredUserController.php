<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Gym;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     *
     * Đăng ký công khai chỉ dành cho Member — Owner/Staff/Trainer được tạo
     * bởi Platform Admin/Gym Owner qua khu quản trị (Ngày 2/3), nên chỉ cần
     * cho chọn Gym đang hoạt động ở bước này.
     */
    public function create(): View
    {
        $gyms = Gym::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('auth.register', ['gyms' => $gyms]);
    }

    /**
     * Handle an incoming registration request.
     *
     * Chỉ tạo tài khoản User (role=member, gym_id). Hồ sơ Member (member_code,
     * status...) sẽ được tạo ở bước chọn gói/membership (Khối 7-8), theo đúng
     * workflow mục 26, tránh trùng lặp logic sinh member_code.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'gym_id' => ['required', 'integer', Rule::exists('gyms', 'id')->where('is_active', true)],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => User::ROLE_MEMBER,
            'gym_id' => $request->gym_id,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect($user->dashboardPath());
    }
}
