<?php

namespace Database\Seeders;

use App\Modules\RiskManagement\Model\UserViolation;
use App\Modules\User\Model\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Modules\User\Model\Enums\UserRole;
use App\Modules\User\Model\CustomerProfile;
use App\Modules\User\Model\DriverProfile;

class UserViolationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Xóa dữ liệu cũ
        UserViolation::query()->delete();

        // Lấy 1 driver và 1 customer để gán vi phạm
        $driver = User::where('role', UserRole::Driver)->first();
        $customer = User::where('role', UserRole::Customer)->first();

        if (!$driver || !$customer) {
            $this->command->warn('Không tìm thấy Driver hoặc Customer để tạo dữ liệu UserViolation.');
            return;
        }

        // Tạo profile giả nếu chưa có
        if (!$driver->driverProfile) {
            DriverProfile::create([
                'user_id' => $driver->id,
                'full_name' => 'Lê Văn Tài xế',
                'driver_group_type' => 1,
                'vehicle_type' => 1,
                'vehicle_name' => 'Honda Wave',
                'vehicle_color' => 1,
                'vehicle_number' => '29A1-12345',
            ]);
        }

        if (!$customer->customerProfile) {
            CustomerProfile::create([
                'user_id' => $customer->id,
                'full_name' => 'Trần Văn Khách',
            ]);
        }

        // Tạo 2 vi phạm cho Driver (để đạt trạng thái WARNED - 2 lần)
        UserViolation::create([
            'user_id' => $driver->id,
            'type' => 'ATTITUDE',
            'reason' => 'Tài xế có thái độ không tốt với khách hàng (lần 1).',
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);

        UserViolation::create([
            'user_id' => $driver->id,
            'type' => 'ATTITUDE',
            'reason' => 'Quát mắng khách hàng khi đang di chuyển và yêu cầu tip thêm tiền mặt trái quy định.',
            'created_at' => now()->subDays(1),
            'updated_at' => now()->subDays(1),
        ]);

        // Tạo 3 vi phạm cho Customer (để đạt trạng thái SUSPENDED - 3 lần)
        UserViolation::create([
            'user_id' => $customer->id,
            'type' => 'SPAM_BOOKING',
            'reason' => 'Spam đặt chuyến và hủy (lần 1).',
            'created_at' => now()->subDays(5),
            'updated_at' => now()->subDays(5),
        ]);

        UserViolation::create([
            'user_id' => $customer->id,
            'type' => 'SPAM_BOOKING',
            'reason' => 'Spam đặt chuyến và hủy (lần 2).',
            'created_at' => now()->subDays(3),
            'updated_at' => now()->subDays(3),
        ]);

        UserViolation::create([
            'user_id' => $customer->id,
            'type' => 'SPAM_BOOKING',
            'reason' => 'Đặt 5 chuyến liên tiếp rồi hủy không lý do trong vòng 15 phút, gây ảnh hưởng vận hành.',
            'created_at' => now()->subHours(5),
            'updated_at' => now()->subHours(5),
        ]);

        $this->command->info('Đã tạo dữ liệu mẫu cho UserViolation (Nhật ký vi phạm) thành công!');
    }
}
