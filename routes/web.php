<?php

use App\Http\Controllers\Gym\MemberController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Điểm trung chuyển sau đăng nhập / xác minh email: chuyển tiếp theo dashboardPath()
// của user, để các controller có sẵn của Breeze (VerifyEmailController, ConfirmablePasswordController...)
// vẫn dùng route('dashboard') mà không cần sửa.
Route::get('/dashboard', function () {
    return redirect(Auth::user()->dashboardPath());
})->middleware(['auth', 'verified'])->name('dashboard');

// /admin/* — chỉ Platform Admin.
Route::middleware(['auth', 'verified', 'tenant', 'role:platform_admin'])
    ->prefix('admin')
    ->group(function () {
        Route::view('/', 'placeholders.admin')->name('admin.dashboard');
    });

// /gym/* — Chủ Gym (một số route mở rộng thêm cho Staff, khai báo role riêng theo từng nhóm con).
Route::middleware(['auth', 'verified', 'tenant'])
    ->prefix('gym')
    ->group(function () {
        Route::middleware('role:gym_owner')->group(function () {
            Route::view('/dashboard', 'placeholders.gym-owner')->name('gym.dashboard');
        });

        // Quản lý hội viên: Owner + Staff (mục 3). Route khung để chứng minh
        // tenant isolation (global scope 404 + policy 403) — CRUD đầy đủ ở Khối 7.
        Route::middleware('role:gym_owner,staff')->group(function () {
            Route::get('/members', [MemberController::class, 'index'])->name('gym.members.index');
            Route::get('/members/{member}', [MemberController::class, 'show'])->name('gym.members.show');
        });
    });

// /staff/* — Nhân viên.
Route::middleware(['auth', 'verified', 'tenant', 'role:staff'])
    ->prefix('staff')
    ->group(function () {
        Route::view('/dashboard', 'placeholders.staff')->name('staff.dashboard');
    });

// /trainer/* — Huấn luyện viên.
Route::middleware(['auth', 'verified', 'tenant', 'role:trainer'])
    ->prefix('trainer')
    ->group(function () {
        Route::view('/dashboard', 'placeholders.trainer')->name('trainer.dashboard');
    });

// Hội viên.
Route::middleware(['auth', 'verified', 'tenant', 'role:member'])->group(function () {
    Route::view('/home', 'placeholders.member')->name('member.home');
});

Route::middleware(['auth', 'tenant'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
