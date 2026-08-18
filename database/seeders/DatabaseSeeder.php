<?php

namespace Database\Seeders;

use App\Models\Gym;
use App\Models\Member;
use App\Models\Package;
use App\Models\Promotion;
use App\Models\Staff;
use App\Models\Trainer;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    private const STAFF_PER_GYM = 2;

    private const TRAINERS_PER_GYM = 3;

    private const MEMBERS_PER_GYM = 15;

    private const PROMOTIONS_PER_GYM = 2;

    // Mỗi Gym có đủ 4 loại gói theo thời hạn cố định (thay vì random) để demo dễ so sánh.
    private const PACKAGE_DURATIONS_DAYS = [30, 90, 180, 365];

    private const GYMS = [
        [
            'name' => 'FitZone Hoàn Kiếm',
            'slug' => 'fitzone-hoan-kiem',
            'prefix' => 'fitzone',
            'code' => 'FZ',
            'address' => '12 Hàng Bài, Hoàn Kiếm, Hà Nội',
        ],
        [
            'name' => 'PowerHouse Hà Nội',
            'slug' => 'powerhouse-hanoi',
            'prefix' => 'powerhouse',
            'code' => 'PH',
            'address' => '45 Trần Duy Hưng, Cầu Giấy, Hà Nội',
        ],
        [
            'name' => 'Elite Fitness',
            'slug' => 'elite-fitness',
            'prefix' => 'elite',
            'code' => 'EF',
            'address' => '88 Nguyễn Trãi, Thanh Xuân, Hà Nội',
        ],
    ];

    /**
     * Seed the application's database.
     *
     * QUAN TRỌNG: seeder chạy qua CLI, không có auth()->user() nên trait
     * BelongsToGym KHÔNG tự gán được gym_id. Mọi bản ghi bên dưới đều truyền
     * gym_id tường minh, không dựa vào auto-fill.
     */
    public function run(): void
    {
        $demoAccounts = [];

        $admin = User::factory()->create([
            'gym_id' => null,
            'role' => User::ROLE_PLATFORM_ADMIN,
            'name' => 'Platform Admin',
            'email' => 'admin@gymhub.test',
        ]);
        $demoAccounts[] = ['Platform Admin', $admin->email, 'password', 'platform_admin', '-'];

        foreach (self::GYMS as $def) {
            $gym = Gym::factory()->create([
                'name' => $def['name'],
                'slug' => $def['slug'],
                'code' => $def['code'],
                'address' => $def['address'],
                'phone' => '024'.fake()->numerify('#######'),
                'email' => "contact@{$def['prefix']}.test",
                'is_active' => true,
            ]);

            $owner = User::factory()->create([
                'gym_id' => $gym->id,
                'role' => User::ROLE_GYM_OWNER,
                'name' => 'Chủ '.$def['name'],
                'email' => "owner@{$def['prefix']}.test",
            ]);
            $demoAccounts[] = ["Chủ Gym - {$def['name']}", $owner->email, 'password', 'gym_owner', $def['name']];

            for ($i = 1; $i <= self::STAFF_PER_GYM; $i++) {
                $staffUser = User::factory()->create([
                    'gym_id' => $gym->id,
                    'role' => User::ROLE_STAFF,
                    'name' => "Nhân viên {$i} - {$def['name']}",
                    'email' => "staff{$i}@{$def['prefix']}.test",
                ]);

                Staff::factory()->create([
                    'gym_id' => $gym->id,
                    'user_id' => $staffUser->id,
                ]);

                $demoAccounts[] = ["Staff {$i} - {$def['name']}", $staffUser->email, 'password', 'staff', $def['name']];
            }

            for ($i = 1; $i <= self::TRAINERS_PER_GYM; $i++) {
                $trainerUser = User::factory()->create([
                    'gym_id' => $gym->id,
                    'role' => User::ROLE_TRAINER,
                    'name' => "PT {$i} - {$def['name']}",
                    'email' => "trainer{$i}@{$def['prefix']}.test",
                ]);

                Trainer::factory()->create([
                    'gym_id' => $gym->id,
                    'user_id' => $trainerUser->id,
                ]);

                $demoAccounts[] = ["Trainer {$i} - {$def['name']}", $trainerUser->email, 'password', 'trainer', $def['name']];
            }

            for ($i = 1; $i <= self::MEMBERS_PER_GYM; $i++) {
                $memberUser = User::factory()->create([
                    'gym_id' => $gym->id,
                    'role' => User::ROLE_MEMBER,
                    'name' => "Hội viên {$i} - {$def['name']}",
                    'email' => "member{$i}@{$def['prefix']}.test",
                ]);

                Member::factory()->create([
                    'gym_id' => $gym->id,
                    'user_id' => $memberUser->id,
                    'member_code' => sprintf('%s-%04d', $def['code'], $i),
                ]);

                if ($i <= 2) {
                    $demoAccounts[] = ["Member {$i} - {$def['name']}", $memberUser->email, 'password', 'member', $def['name']];
                }
            }
            $demoAccounts[] = [
                'Member 3–15 - '.$def['name'],
                "member3@{$def['prefix']}.test … member15@{$def['prefix']}.test",
                'password', 'member', $def['name'],
            ];

            foreach (self::PACKAGE_DURATIONS_DAYS as $duration) {
                Package::factory()->create([
                    'gym_id' => $gym->id,
                    'duration_days' => $duration,
                ]);
            }

            for ($i = 1; $i <= self::PROMOTIONS_PER_GYM; $i++) {
                Promotion::factory()->create([
                    'gym_id' => $gym->id,
                    'code' => "{$def['code']}SALE{$i}",
                ]);
            }
        }

        $this->command->table(
            ['Vai trò', 'Email', 'Mật khẩu', 'Role', 'Gym'],
            $demoAccounts
        );

        $this->command->info(
            'Đã seed: 1 platform admin, '.count(self::GYMS).' gym, mỗi gym 1 owner + '
            .self::STAFF_PER_GYM.' staff + '.self::TRAINERS_PER_GYM.' trainer + '
            .self::MEMBERS_PER_GYM.' member + '.count(self::PACKAGE_DURATIONS_DAYS).' package + '
            .self::PROMOTIONS_PER_GYM.' promotion.'
        );
    }
}
