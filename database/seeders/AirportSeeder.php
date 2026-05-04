<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AirportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $airports = [
            [
                'name' => 'Sân bay Quốc tế Nội Bài',
                'code' => 'HAN',
                'lat'  => 21.2187,
                'lng'  => 105.8041,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Sân bay Quốc tế Tân Sơn Nhất',
                'code' => 'SGN',
                'lat'  => 10.8185,
                'lng'  => 106.6588,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Sân bay Quốc tế Đà Nẵng',
                'code' => 'DAD',
                'lat'  => 16.0439,
                'lng'  => 108.1995,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Sân bay Quốc tế Cam Ranh',
                'code' => 'CXR',
                'lat'  => 11.9981,
                'lng'  => 109.2193,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Sân bay Quốc tế Phú Quốc',
                'code' => 'PQC',
                'lat'  => 10.1695,
                'lng'  => 103.9961,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Sân bay Quốc tế Cát Bi',
                'code' => 'HPH',
                'lat'  => 20.8189,
                'lng'  => 106.7247,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Sân bay Quốc tế Vinh',
                'code' => 'VII',
                'lat'  => 18.7360,
                'lng'  => 105.6705,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Sân bay Quốc tế Liên Khương',
                'code' => 'DLI',
                'lat'  => 11.7505,
                'lng'  => 108.3695,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Sân bay Quốc tế Phú Bài',
                'code' => 'HUI',
                'lat'  => 16.4017,
                'lng'  => 107.7031,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Sân bay Quốc tế Cần Thơ',
                'code' => 'VCA',
                'lat'  => 10.0851,
                'lng'  => 105.7121,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('airports')->insert($airports);
    }
}
