<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class VehicleTypeSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            [1, 'bike', 'Xe Máy', 'Nhanh, tiết kiệm — phù hợp đường ngắn', 1, '2–5 phút', true, 1],
            [2, 'car_4', 'Ô Tô 4 Chỗ', 'Thoải mái cho 1–3 hành khách', 3, '3–7 phút', true, 2],
            [3, 'car_7', 'Ô Tô 7 Chỗ', 'Rộng rãi cho nhóm 4–6 người', 6, '5–10 phút', true, 3],
            [4, 'car_9', 'Ô Tô 9 Chỗ', 'Lý tưởng cho nhóm đông hoặc nhiều hành lý', 8, '7–15 phút', true, 4],
            [5, 'car_shared', 'Xe Ghép (Liên tỉnh)', 'Tiết kiệm, đi chung với hành khách khác', 1, 'Theo lịch hẹn', true, 5],
            [6, 'chauffeur', 'Lái hộ (Xe khách)', 'Tài xế lái xe của chính bạn', 4, '10–20 phút', true, 6],
        ];

        foreach ($rows as [$id, $code, $name, $description, $capacity, $waitTime, $isActive, $sortOrder]) {
            DB::table('vehicle_types')->updateOrInsert(
                ['id' => $id],
                [
                    'code' => $code,
                    'name_vi' => $name,
                    'description_vi' => $description,
                    'capacity' => $capacity,
                    'estimated_wait_time' => $waitTime,
                    'is_active' => $isActive,
                    'sort_order' => $sortOrder,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
