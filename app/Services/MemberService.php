<?php

namespace App\Services;

use App\Models\Gym;
use App\Models\Member;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Business logic tạo/cập nhật hội viên. Đặt ở Service (không phải Controller)
 * vì tạo hội viên đụng tới 2 bảng (users + members) trong 1 transaction, và
 * việc sinh member_code cần xử lý an toàn khi có nhiều request đồng thời.
 */
class MemberService
{
    private const MAX_CODE_GENERATION_ATTEMPTS = 3;

    /**
     * Tạo tài khoản User (role=member) + hồ sơ Member trong cùng 1 transaction.
     * Mật khẩu được sinh ngẫu nhiên — hội viên tự đặt lại qua "Quên mật khẩu".
     */
    public function create(Gym $gym, array $attributes): Member
    {
        $attempt = 0;

        while (true) {
            $attempt++;

            try {
                return DB::transaction(function () use ($gym, $attributes) {
                    $user = User::create([
                        'gym_id' => $gym->id,
                        'role' => User::ROLE_MEMBER,
                        'name' => $attributes['name'],
                        'email' => $attributes['email'],
                        'phone' => $attributes['phone'] ?? null,
                        'password' => Hash::make(Str::random(32)),
                    ]);

                    return Member::create([
                        'gym_id' => $gym->id,
                        'user_id' => $user->id,
                        'member_code' => $this->nextMemberCode($gym),
                        'date_of_birth' => $attributes['date_of_birth'] ?? null,
                        'gender' => $attributes['gender'] ?? null,
                        'address' => $attributes['address'] ?? null,
                        'emergency_contact' => $attributes['emergency_contact'] ?? null,
                        'height' => $attributes['height'] ?? null,
                        'weight' => $attributes['weight'] ?? null,
                        'status' => Member::STATUS_ACTIVE,
                        'joined_at' => $attributes['joined_at'] ?? now()->toDateString(),
                        'notes' => $attributes['notes'] ?? null,
                    ]);
                });
            } catch (QueryException $e) {
                // Phòng vệ lớp 2: nếu 2 request vẫn đụng độ member_code (hiếm, vì
                // đã khóa dòng Gym bên dưới) thì thử sinh lại thay vì crash thẳng.
                if ($this->isDuplicateMemberCode($e) && $attempt < self::MAX_CODE_GENERATION_ATTEMPTS) {
                    continue;
                }

                throw $e;
            }
        }
    }

    /**
     * Cập nhật thông tin hội viên. Name/email/phone nằm ở bảng users, các
     * trường còn lại nằm ở bảng members — cập nhật cả hai trong 1 transaction.
     */
    public function update(Member $member, array $attributes): Member
    {
        return DB::transaction(function () use ($member, $attributes) {
            $member->user->update([
                'name' => $attributes['name'],
                'email' => $attributes['email'],
                'phone' => $attributes['phone'] ?? null,
            ]);

            $member->update([
                'date_of_birth' => $attributes['date_of_birth'] ?? null,
                'gender' => $attributes['gender'] ?? null,
                'address' => $attributes['address'] ?? null,
                'emergency_contact' => $attributes['emergency_contact'] ?? null,
                'height' => $attributes['height'] ?? null,
                'weight' => $attributes['weight'] ?? null,
                'status' => $attributes['status'] ?? $member->status,
                'joined_at' => $attributes['joined_at'] ?? $member->joined_at,
                'notes' => $attributes['notes'] ?? null,
            ]);

            return $member->fresh(['user']);
        });
    }

    /**
     * Sinh member_code kế tiếp cho 1 Gym, an toàn khi có nhiều request tạo
     * member cùng lúc: khóa dòng Gym (SELECT ... FOR UPDATE) để tuần tự hóa
     * các transaction tạo member CỦA CÙNG GYM ĐÓ — Gym khác không bị ảnh
     * hưởng vì đây là khóa theo dòng (row lock), không phải khóa bảng.
     * Không dùng count()+1 đơn thuần vì 2 transaction đọc cùng lúc sẽ ra
     * cùng 1 số thứ tự trước khi commit.
     */
    private function nextMemberCode(Gym $gym): string
    {
        /** @var Gym $lockedGym */
        $lockedGym = Gym::query()->whereKey($gym->id)->lockForUpdate()->firstOrFail();

        if (! $lockedGym->code) {
            throw new RuntimeException(
                "Gym '{$lockedGym->name}' chưa được cấu hình mã (code) để sinh member_code."
            );
        }

        $prefix = $lockedGym->code;

        $maxSuffix = Member::withoutGlobalScope('gym')
            ->where('gym_id', $lockedGym->id)
            ->where('member_code', 'like', $prefix.'-%')
            ->lockForUpdate()
            ->get(['member_code'])
            ->map(fn (Member $m) => (int) substr($m->member_code, strlen($prefix) + 1))
            ->max();

        return sprintf('%s-%04d', $prefix, ($maxSuffix ?? 0) + 1);
    }

    private function isDuplicateMemberCode(QueryException $e): bool
    {
        return str_contains($e->getMessage(), 'members_gym_id_member_code_unique');
    }
}
