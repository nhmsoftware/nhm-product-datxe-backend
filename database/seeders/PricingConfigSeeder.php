<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PricingConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $configs = [
            [
                'vehicle_type' => 1, // Bike
                'base_price' => 12000,
                'distance_rate' => 4000,
                'time_rate' => 500,
                'min_fare' => 15000,
                'surge_multiplier' => 1.0,
                'commission_rate' => 20,
            ],
            [
                'vehicle_type' => 2, // Car 4
                'base_price' => 20000,
                'distance_rate' => 12000,
                'time_rate' => 1000,
                'min_fare' => 30000,
                'surge_multiplier' => 1.0,
                'commission_rate' => 25,
            ],
            [
                'vehicle_type' => 3, // Car 7
                'base_price' => 25000,
                'distance_rate' => 15000,
                'time_rate' => 1500,
                'min_fare' => 40000,
                'surge_multiplier' => 1.0,
                'commission_rate' => 25,
            ],
            [
                'vehicle_type' => 4, // Car 9
                'base_price' => 30000,
                'distance_rate' => 18000,
                'time_rate' => 2000,
                'min_fare' => 50000,
                'surge_multiplier' => 1.0,
                'commission_rate' => 30,
            ],
        ];

        foreach ($configs as $config) {
            \App\Modules\Pricing\Model\PricingConfig::updateOrCreate(
                ['vehicle_type' => $config['vehicle_type']],
                $config
            );
        }

        // Global Setting for Free Mode
        \App\Modules\Pricing\Model\PricingGlobalSetting::updateOrCreate(
            ['is_free_mode' => false],
            ['is_free_mode' => false]
        );

        // Seed Admin User
        \App\Modules\User\Model\User::updateOrCreate(
            ['email' => 'admin@nhm.com'],
            [
                'phone' => '0123456789',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'role' => 1,
                'is_active' => true,
            ]
        );
    }
}
