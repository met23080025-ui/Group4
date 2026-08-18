<?php

use App\Http\Controllers\Gym\MemberController;
use App\Http\Controllers\Gym\MembershipController;
use App\Http\Controllers\Gym\PackageController;
use App\Http\Controllers\Gym\PromotionController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentController;
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

        // Quản lý hội viên: Owner + Staff (mục 3). Route cụ thể (create/trashed)
        // phải khai báo TRƯỚC route {member} để không bị hiểu nhầm thành ID.
        Route::middleware('role:gym_owner,staff')
            ->prefix('members')
            ->name('gym.members.')
            ->group(function () {
                Route::get('/', [MemberController::class, 'index'])->name('index');
                Route::get('/trashed', [MemberController::class, 'trashed'])->name('trashed');
                Route::get('/create', [MemberController::class, 'create'])->name('create');
                Route::post('/', [MemberController::class, 'store'])->name('store');
                Route::post('/{member}/restore', [MemberController::class, 'restore'])->name('restore');
                Route::get('/{member}', [MemberController::class, 'show'])->name('show');
                Route::get('/{member}/edit', [MemberController::class, 'edit'])->name('edit');
                Route::put('/{member}', [MemberController::class, 'update'])->name('update');
                Route::delete('/{member}', [MemberController::class, 'destroy'])->name('destroy');
            });

        // Gói tập + khuyến mãi + membership: Owner + Staff (mục 3, mục 26).
        Route::middleware('role:gym_owner,staff')->group(function () {
            Route::prefix('packages')->name('gym.packages.')->group(function () {
                Route::get('/', [PackageController::class, 'index'])->name('index');
                Route::get('/create', [PackageController::class, 'create'])->name('create');
                Route::post('/', [PackageController::class, 'store'])->name('store');
                Route::post('/{package}/promotions', [PackageController::class, 'attachPromotion'])->name('promotions.attach');
                Route::delete('/{package}/promotions/{promotion}', [PackageController::class, 'detachPromotion'])->name('promotions.detach');
                Route::get('/{package}', [PackageController::class, 'show'])->name('show');
                Route::get('/{package}/edit', [PackageController::class, 'edit'])->name('edit');
                Route::put('/{package}', [PackageController::class, 'update'])->name('update');
                Route::delete('/{package}', [PackageController::class, 'destroy'])->name('destroy');
            });

            Route::prefix('promotions')->name('gym.promotions.')->group(function () {
                Route::get('/', [PromotionController::class, 'index'])->name('index');
                Route::get('/create', [PromotionController::class, 'create'])->name('create');
                Route::post('/', [PromotionController::class, 'store'])->name('store');
                Route::get('/{promotion}/edit', [PromotionController::class, 'edit'])->name('edit');
                Route::put('/{promotion}', [PromotionController::class, 'update'])->name('update');
                Route::delete('/{promotion}', [PromotionController::class, 'destroy'])->name('destroy');
            });

            Route::prefix('memberships')->name('gym.memberships.')->group(function () {
                Route::get('/', [MembershipController::class, 'index'])->name('index');
                Route::get('/create', [MembershipController::class, 'create'])->name('create');
                Route::post('/', [MembershipController::class, 'store'])->name('store');
                Route::post('/{membership}/payment', [PaymentController::class, 'store'])->name('payment.store');
                Route::get('/{membership}', [MembershipController::class, 'show'])->name('show');
            });

            // Thanh toán: Staff/Owner tạo QR, xem danh sách chờ, và xác nhận đã
            // nhận tiền (Khối 2 — kích hoạt Membership + sinh Invoice, 1 transaction).
            Route::prefix('payments')->name('gym.payments.')->group(function () {
                Route::get('/', [PaymentController::class, 'index'])->name('index');
                Route::post('/{payment}/confirm', [PaymentController::class, 'confirm'])->name('confirm');
                Route::get('/{payment}', [PaymentController::class, 'show'])->name('show');
            });

            Route::get('/invoices/{invoice}/download', [InvoiceController::class, 'download'])->name('gym.invoices.download');
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

    Route::prefix('payments')->name('member.payments.')->group(function () {
        Route::get('/', [PaymentController::class, 'mine'])->name('index');
        Route::get('/{payment}', [PaymentController::class, 'show'])->name('show');
    });

    Route::get('/invoices/{invoice}/download', [InvoiceController::class, 'download'])->name('member.invoices.download');
});

Route::middleware(['auth', 'tenant'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
