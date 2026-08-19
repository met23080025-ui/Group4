<?php

use App\Http\Controllers\BodyMeasurementController;
use App\Http\Controllers\ClassBookingController;
use App\Http\Controllers\Gym\AttendanceController;
use App\Http\Controllers\Gym\MemberController;
use App\Http\Controllers\Gym\MembershipController;
use App\Http\Controllers\Gym\PackageController;
use App\Http\Controllers\Gym\PromotionController;
use App\Http\Controllers\Gym\ScheduleController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MemberQrController;
use App\Http\Controllers\NutritionPlanController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TrainerController;
use App\Http\Controllers\WorkoutPlanController;
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
                Route::post('/{member}/assign-trainer', [MemberController::class, 'assignTrainer'])->name('assign-trainer');
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

            // Lớp/lịch tập (mục 12): CRUD chỉ Owner/Staff, qua SchedulePolicy.
            Route::prefix('schedules')->name('gym.schedules.')->group(function () {
                Route::get('/', [ScheduleController::class, 'index'])->name('index');
                Route::get('/create', [ScheduleController::class, 'create'])->name('create');
                Route::post('/', [ScheduleController::class, 'store'])->name('store');
                Route::get('/{schedule}', [ScheduleController::class, 'show'])->name('show');
                Route::get('/{schedule}/edit', [ScheduleController::class, 'edit'])->name('edit');
                Route::put('/{schedule}', [ScheduleController::class, 'update'])->name('update');
                Route::delete('/{schedule}', [ScheduleController::class, 'destroy'])->name('destroy');
            });

            // QR check-in (mục 6): Staff/Owner nhập/quét token QR của hội viên.
            Route::prefix('checkin')->name('gym.checkin.')->group(function () {
                Route::get('/', [AttendanceController::class, 'index'])->name('index');
                Route::post('/', [AttendanceController::class, 'store'])->name('store');
            });
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
        Route::get('/dashboard', [TrainerController::class, 'dashboard'])->name('trainer.dashboard');
    });

// Hội viên.
Route::middleware(['auth', 'verified', 'tenant', 'role:member'])->group(function () {
    Route::view('/home', 'placeholders.member')->name('member.home');

    Route::prefix('payments')->name('member.payments.')->group(function () {
        Route::get('/', [PaymentController::class, 'mine'])->name('index');
        Route::get('/{payment}', [PaymentController::class, 'show'])->name('show');
    });

    Route::get('/invoices/{invoice}/download', [InvoiceController::class, 'download'])->name('member.invoices.download');

    // QR check-in của chính mình (mục 6): Staff/Owner quét mã này để check-in.
    Route::get('/qr', [MemberQrController::class, 'show'])->name('member.qr.show');

    // Lớp tập (mục 12): xem lớp sắp diễn ra của gym mình + đặt lớp.
    Route::prefix('schedules')->name('member.schedules.')->group(function () {
        Route::get('/', [ClassBookingController::class, 'index'])->name('index');
        Route::post('/{schedule}/book', [ClassBookingController::class, 'store'])->name('book');
    });

    // Booking của chính mình: xem + huỷ.
    Route::prefix('bookings')->name('member.bookings.')->group(function () {
        Route::get('/', [ClassBookingController::class, 'mine'])->name('index');
        Route::delete('/{booking}', [ClassBookingController::class, 'destroy'])->name('destroy');
    });
});

Route::middleware(['auth', 'tenant'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Body measurement + workout/nutrition plan (Khối 6): dùng chung cho Owner/
// Staff/Trainer(đã phân công)/Member(chỉ xem) — Policy phân định quyền theo
// vai trò, KHÔNG theo route middleware (route model binding {member} đã tự
// trả 404 cross-tenant qua global scope BelongsToGym; cross-trainer bị chặn
// ở tầng Policy, xem BodyMeasurementPolicy/WorkoutPlanPolicy/NutritionPlanPolicy).
Route::middleware(['auth', 'verified', 'tenant'])->group(function () {
    Route::prefix('members/{member}')->name('members.')->group(function () {
        Route::get('/measurements', [BodyMeasurementController::class, 'index'])->name('measurements.index');
        Route::post('/measurements', [BodyMeasurementController::class, 'store'])->name('measurements.store');

        Route::get('/workout-plans', [WorkoutPlanController::class, 'index'])->name('workout-plans.index');
        Route::post('/workout-plans', [WorkoutPlanController::class, 'store'])->name('workout-plans.store');

        Route::get('/nutrition-plans', [NutritionPlanController::class, 'index'])->name('nutrition-plans.index');
        Route::post('/nutrition-plans', [NutritionPlanController::class, 'store'])->name('nutrition-plans.store');
    });

    Route::post('/workout-plans/{workoutPlan}/items', [WorkoutPlanController::class, 'storeItem'])->name('workout-plans.items.store');
    Route::post('/nutrition-plans/{nutritionPlan}/items', [NutritionPlanController::class, 'storeItem'])->name('nutrition-plans.items.store');
});

require __DIR__.'/auth.php';
