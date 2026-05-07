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
        
        echo "Merchant test user created: 0988888888 / 123456\n";
    }
}
