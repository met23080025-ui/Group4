<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['gym_id', 'name', 'email', 'password', 'role', 'phone', 'avatar_path', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /**
     * Notifiable BẮT BUỘC phải giữ: CanResetPassword::sendPasswordResetNotification()
     * và MustVerifyEmail::sendEmailVerificationNotification() gọi $this->notify(...),
     * chỉ có trait Notifiable mới cung cấp method notify(). Trait này cũng tự định nghĩa
     * sẵn method notifications() trỏ tới bảng notifications mặc định của Laravel (uuid,
     * polymorphic) — khác schema với model Notification tự định nghĩa của GymHub (mục 16).
     * Method notifications() bên dưới GHI ĐÈ (override) method của trait để trỏ đúng
     * sang App\Models\Notification, tránh xung đột tên bảng mà vẫn giữ được notify().
     */

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    // Vai trò nền tảng: quản trị toàn bộ các Gym/tenant, không thuộc gym nào (gym_id = null).
    public const ROLE_PLATFORM_ADMIN = 'platform_admin';

    // Chủ Gym: quản lý toàn bộ hoạt động của Gym mình (gym_id bắt buộc).
    public const ROLE_GYM_OWNER = 'gym_owner';

    // Nhân viên Gym: vận hành hàng ngày (gym_id bắt buộc).
    public const ROLE_STAFF = 'staff';

    // Huấn luyện viên (gym_id bắt buộc).
    public const ROLE_TRAINER = 'trainer';

    // Hội viên (gym_id bắt buộc).
    public const ROLE_MEMBER = 'member';

    public const ROLES = [
        self::ROLE_PLATFORM_ADMIN,
        self::ROLE_GYM_OWNER,
        self::ROLE_STAFF,
        self::ROLE_TRAINER,
        self::ROLE_MEMBER,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * KHÔNG áp trait BelongsToGym cho User: guard xác thực (Auth::user())
     * tự truy vấn lại chính model User để resolve người dùng hiện tại, nên
     * nếu global scope 'gym' gọi auth()->user() bên trong sẽ gây đệ quy vô hạn
     * (User::query() -> scope gọi auth()->user() -> lại truy vấn User::query()...).
     * Vì vậy việc phân tách dữ liệu theo Gym cho "người dùng" được thực hiện ở
     * tầng model nghiệp vụ (Member/Trainer/Staff — đều có BelongsToGym), là nơi
     * thực sự diễn ra các thao tác quản lý hội viên/PT/nhân viên theo từng Gym.
     */
    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    public function member(): HasOne
    {
        return $this->hasOne(Member::class);
    }

    public function trainer(): HasOne
    {
        return $this->hasOne(Trainer::class);
    }

    public function staff(): HasOne
    {
        return $this->hasOne(Staff::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function isPlatformAdmin(): bool
    {
        return $this->role === self::ROLE_PLATFORM_ADMIN;
    }

    /**
     * Đường dẫn dashboard tương ứng với vai trò, dùng để điều hướng sau khi đăng nhập (mục 20).
     */
    public function dashboardPath(): string
    {
        return match ($this->role) {
            self::ROLE_PLATFORM_ADMIN => '/admin',
            self::ROLE_GYM_OWNER => '/gym/dashboard',
            self::ROLE_STAFF => '/staff/dashboard',
            self::ROLE_TRAINER => '/trainer/dashboard',
            default => '/home',
        };
    }
}
