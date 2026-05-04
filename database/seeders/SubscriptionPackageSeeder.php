<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class SubscriptionPackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('subscription_packages')->insert([
            [
                'name'                          => 'Gói Ngày',
                'description'                   => 'Gói thành viên theo ngày, giảm 10% phí dịch vụ.',
                'price'                         => 10000,
                'duration_days'                 => 1,
        
                'service_fee_reduction_percent' => 10.00,
                'is_active'                     => true,
                'created_at'                    => now(),
                'updated_at'                    => now(),
            ],
            [
                'name'                          => 'Gói Tuần',
                'description'                   => 'Gói thành viên theo tuần, giảm 15% phí dịch vụ.',
                'price'                         => 60000,
                'duration_days'                 => 7,
                'service_fee_reduction_percent' => 15.00,
                'is_active'                     => true,
                'created_at'                    => now(),
                'updated_at'                    => now(),
            ],
            [
                'name'                          => 'Gói Tháng',
                'description'                   => 'Gói thành viên theo tháng, giảm 25% phí dịch vụ.',
                'price'                         => 200000,
                'duration_days'                 => 30,
                'service_fee_reduction_percent' => 25.00,
                'is_active'                     => true,
                'created_at'                    => now(),
                'updated_at'                    => now(),
            ],
        ]);
    }
}
