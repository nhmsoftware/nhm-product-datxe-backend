<?php

namespace Database\Seeders;

use App\Modules\User\Model\DriverProfile;
use App\Modules\User\Model\User;
use Illuminate\Database\Seeder;

class DriverProfileSeeder extends Seeder
{
    public function run(): void
    {
        // Lấy danh sách ID để gán ngẫu nhiên
        $userIds = User::pluck('id')->toArray();

        if (empty($userIds)) {
            $this->command->warn('Vui lòng tạo dữ liệu bảng users trước!');
            return;
        }

        $faker = \Faker\Factory::create('vi_VN');

        foreach (array_slice($userIds, 0, 5) as $userId) { // Tạo mẫu cho 5 user đầu tiên
            DriverProfile::create([
                'user_id' => $userId,
                'full_name' => $faker->name,

                // Tạm thời gán null vì chưa có dữ liệu bảng driver_groups
                'driver_group_id' => null,

                'driver_group_type' => rand(1, 3), // Giả định: 1: Cá nhân, 2: Doanh nghiệp, 3: Đối tác
                'vehicle_type' => rand(1, 2),      // Giả định: 1: Xe máy, 2: Ô tô
                'vehicle_name' => $faker->randomElement(['Honda Wave Alpha', 'Yamaha Exciter', 'Toyota Vios', 'Hyundai Grand i10']),
                'vehicle_color' => rand(1, 5),     // Giả định: 1: Đen, 2: Trắng, 3: Đỏ...
                'vehicle_number' => rand(29, 30) . $faker->bothify('-?? ###.##'),
                'is_online' => $faker->boolean(70), // 70% tỉ lệ online
                'current_lat' => $faker->latitude(20.9, 21.1), // Tọa độ khu vực HN
                'current_lng' => $faker->longitude(105.7, 105.9),
                'status' => 1, // Giả định: 1: Sẵn sàng, 2: Đang bận, 3: Khóa
                'cooldown_until' => null,
                'cancel_count_today' => rand(0, 2),
            ]);
        }
    }
}
