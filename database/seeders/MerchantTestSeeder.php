<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\User\Model\User;
use App\Modules\User\Model\MerchantProfile;
use App\Modules\User\Model\Enums\UserRole;
use App\Modules\User\Model\Enums\KycStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MerchantTestSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['phone' => '0988888888'],
            [
                'email' => 'merchant@test.com',
                'password' => Hash::make('123456'),
                'role' => UserRole::Merchants,
                'is_active' => true,
                'is_verified' => true,
                'is_phone_verified' => true,
            ]
        );

        MerchantProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'store_name' => 'Cửa hàng Test API',
                'store_address' => '123 Đường Test, Hà Nội',
                'is_open' => true,
                'status' => KycStatus::Approved,
                'average_rating' => 5.0,
                'total_orders' => 0,
            ]
        );

        // Seeding thêm quán mockup từ giao diện
        $user2 = User::updateOrCreate(
            ['phone' => '0988888889'],
            [
                'email' => 'merchant2@test.com',
                'password' => Hash::make('123456'),
                'role' => UserRole::Merchants,
                'is_active' => true,
                'is_verified' => true,
                'is_phone_verified' => true,
            ]
        );
        MerchantProfile::updateOrCreate(
            ['user_id' => $user2->id],
            [
                'store_name' => 'Phở Hùng - Nguyễn Trãi',
                'store_address' => 'Nguyễn Trãi, Hà Nội',
                'is_open' => true,
                'status' => KycStatus::Approved,
                'average_rating' => 4.8,
                'total_orders' => 150,
            ]
        );

        $user3 = User::updateOrCreate(
            ['phone' => '0988888890'],
            [
                'email' => 'merchant3@test.com',
                'password' => Hash::make('123456'),
                'role' => UserRole::Merchants,
                'is_active' => true,
                'is_verified' => true,
                'is_phone_verified' => true,
            ]
        );
        MerchantProfile::updateOrCreate(
            ['user_id' => $user3->id],
            [
                'store_name' => 'The Burger Joint',
                'store_address' => 'Cầu Giấy, Hà Nội',
                'is_open' => true,
                'status' => KycStatus::Approved,
                'average_rating' => 4.5,
                'total_orders' => 85,
            ]
        );
        
        echo "Merchant test users created: 0988888888, 0988888889, 0988888890\n";
    }
}
