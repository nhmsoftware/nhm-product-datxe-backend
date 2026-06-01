<?php

namespace Database\Seeders;

use App\Modules\User\Model\DriverProfile;
use App\Modules\User\Model\Enums\UserRole;
use App\Modules\User\Model\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DummyDriverSeeder extends Seeder
{
    /**
     * Tạo tài khoản tài xế giả định để test.
     */
    public function run(): void
    {
        $drivers = [
            [
                'user' => [
                    'phone'             => '0911111101',
                    'email'             => 'driver01@nhm-datxe.com',
                    'password'          => Hash::make('Driver@123'),
                    'role'              => UserRole::Driver->value,
                    'is_active'         => true,
                    'is_verified'       => true,
                    'is_phone_verified' => true,
                    'avatar'            => null,
                    'address'           => '12 Trần Hưng Đạo, Hoàn Kiếm, Hà Nội',
                    'citizen_id'        => '001234567890',
                ],
                'profile' => [
                    'full_name'           => 'Nguyễn Văn Tài',
                    'driver_group_id'     => null,
                    'driver_group_type'   => 1,  // 1: Cá nhân
                    'vehicle_type'        => 2,  // 2: Ô tô
                    'vehicle_name'        => 'Toyota Vios',
                    'vehicle_color'       => 2,  // 2: Trắng
                    'vehicle_number'      => '29A-123.45',
                    'license_number'      => 'B2-HN-123456',
                    'is_online'           => true,
                    'current_lat'         => 21.0285,
                    'current_lng'         => 105.8542,
                    'status'              => 1,  // 1: Sẵn sàng
                    'cooldown_until'      => null,
                    'cancel_count_today'  => 0,
                    'average_rating'      => 4.85,
                    'total_trips'         => 152,
                    'bank_name'           => 'Vietcombank',
                    'bank_account_number' => '1234567890',
                    'bank_account_holder' => 'NGUYEN VAN TAI',
                ],
            ],
            [
                'user' => [
                    'phone'             => '0911111102',
                    'email'             => 'driver02@nhm-datxe.com',
                    'password'          => Hash::make('Driver@123'),
                    'role'              => UserRole::Driver->value,
                    'is_active'         => true,
                    'is_verified'       => true,
                    'is_phone_verified' => true,
                    'avatar'            => null,
                    'address'           => '45 Lê Duẩn, Đống Đa, Hà Nội',
                    'citizen_id'        => '001234567891',
                ],
                'profile' => [
                    'full_name'           => 'Trần Thị Xe',
                    'driver_group_id'     => null,
                    'driver_group_type'   => 1,  // 1: Cá nhân
                    'vehicle_type'        => 1,  // 1: Xe máy
                    'vehicle_name'        => 'Honda Wave Alpha',
                    'vehicle_color'       => 1,  // 1: Đen
                    'vehicle_number'      => '29B1-456.78',
                    'license_number'      => 'A1-HN-789012',
                    'is_online'           => false,
                    'current_lat'         => 21.0245,
                    'current_lng'         => 105.8412,
                    'status'              => 1,  // 1: Sẵn sàng
                    'cooldown_until'      => null,
                    'cancel_count_today'  => 1,
                    'average_rating'      => 4.70,
                    'total_trips'         => 87,
                    'bank_name'           => 'Techcombank',
                    'bank_account_number' => '9876543210',
                    'bank_account_holder' => 'TRAN THI XE',
                ],
            ],
            [
                'user' => [
                    'phone'             => '0911111103',
                    'email'             => 'driver03@nhm-datxe.com',
                    'password'          => Hash::make('Driver@123'),
                    'role'              => UserRole::Driver->value,
                    'is_active'         => true,
                    'is_verified'       => true,
                    'is_phone_verified' => true,
                    'avatar'            => null,
                    'address'           => '78 Nguyễn Trãi, Thanh Xuân, Hà Nội',
                    'citizen_id'        => '001234567892',
                ],
                'profile' => [
                    'full_name'           => 'Lê Minh Lái',
                    'driver_group_id'     => null,
                    'driver_group_type'   => 2,  // 2: Doanh nghiệp
                    'vehicle_type'        => 2,  // 2: Ô tô
                    'vehicle_name'        => 'Hyundai Grand i10',
                    'vehicle_color'       => 3,  // 3: Đỏ
                    'vehicle_number'      => '30A-789.01',
                    'license_number'      => 'B2-HN-345678',
                    'is_online'           => true,
                    'current_lat'         => 20.9875,
                    'current_lng'         => 105.8023,
                    'status'              => 1,  // 1: Sẵn sàng
                    'cooldown_until'      => null,
                    'cancel_count_today'  => 0,
                    'average_rating'      => 4.92,
                    'total_trips'         => 310,
                    'bank_name'           => 'MB Bank',
                    'bank_account_number' => '5566778899',
                    'bank_account_holder' => 'LE MINH LAI',
                ],
            ],
        ];

        foreach ($drivers as $data) {
            // Tạo hoặc cập nhật user
            $user = User::updateOrCreate(
                ['phone' => $data['user']['phone']],
                $data['user']
            );

            // Tạo hoặc cập nhật driver_profile
            DriverProfile::updateOrCreate(
                ['user_id' => $user->id],
                $data['profile']
            );

            $this->command->info("✅ Đã tạo tài xế: {$data['profile']['full_name']} (phone: {$data['user']['phone']})");
        }

        $this->command->info('');
        $this->command->info('🔑 Thông tin đăng nhập chung:');
        $this->command->info('   Password: Driver@123');
        $this->command->table(
            ['STT', 'Họ tên', 'Số điện thoại', 'Email', 'Phương tiện'],
            array_map(fn ($i, $d) => [
                $i + 1,
                $d['profile']['full_name'],
                $d['user']['phone'],
                $d['user']['email'],
                $d['profile']['vehicle_name'] . ' - ' . $d['profile']['vehicle_number'],
            ], array_keys($drivers), $drivers)
        );
    }
}
