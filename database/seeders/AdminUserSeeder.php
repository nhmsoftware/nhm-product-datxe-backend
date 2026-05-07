<?php

namespace Database\Seeders;

use App\Modules\User\Model\Enums\UserRole;
use App\Modules\User\Model\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['phone' => '0900000001'],
            [
                'email'             => 'admin@nhm-datxe.com',
                'password'          => Hash::make('Password@123'),
                'role'              => UserRole::Admin->value,
                'is_active'         => true,
                'is_verified'       => true,
                'is_phone_verified' => true,
            ]
        );
    }
}
