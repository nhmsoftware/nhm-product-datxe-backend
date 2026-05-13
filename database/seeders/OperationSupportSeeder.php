<?php

namespace Database\Seeders;

use App\Modules\Complaint\Model\Complaint;
use App\Modules\Finance\Model\RefundRequest;
use App\Modules\RiskManagement\Model\UserViolation;
use App\Modules\User\Model\User;
use App\Modules\Ride\Model\Ride;
use App\Modules\User\Model\Enums\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OperationSupportSeeder extends Seeder
{
    public function run(): void
    {
        // Get some users
        $admin = User::where('role', UserRole::Admin->value)->first();
        if (!$admin) {
            $this->call(AdminUserSeeder::class);
            $admin = User::where('role', UserRole::Admin->value)->first();
        }

        $customers = User::where('role', UserRole::Customer->value)->take(5)->get();
        $drivers = User::where('role', UserRole::Driver->value)->take(5)->get();
        
        // If not enough users, use the admin for now or skip
        if ($customers->isEmpty() || $drivers->isEmpty()) {
            $this->command->info('Please run Customer/Driver seeders first.');
            return;
        }

        $rides = Ride::take(10)->get();
        if ($rides->isEmpty()) {
            $this->command->info('Please run Ride seeders first.');
            return;
        }

        // 1. Seed Complaints
        $this->command->info('Seeding Complaints...');
        Complaint::truncate();
        
        $complaintTypes = ['ATTITUDE', 'FRAUD', 'QUALITY', 'OVERCHARGE', 'GPS_ERROR'];
        foreach ($rides as $index => $ride) {
            if ($index > 5) break;
            
            Complaint::create([
                'sender_id' => $customers->random()->id,
                'complaintable_id' => $ride->id,
                'complaintable_type' => Ride::class,
                'type' => $complaintTypes[array_rand($complaintTypes)],
                'content' => 'Lý do khiếu nại mô phỏng cho chuyến xe ' . $ride->id,
                'evidence' => json_encode(['https://placehold.co/600x400?text=Evidence']),
                'status' => 'PENDING',
                'created_at' => now()->subHours(rand(1, 100))
            ]);
        }

        // 2. Seed Refund Requests
        $this->command->info('Seeding Refund Requests...');
        RefundRequest::truncate();

        foreach ($rides as $index => $ride) {
            if ($index < 3 || $index > 7) continue;
            
            RefundRequest::create([
                'customer_id' => $customers->random()->id,
                'refundable_id' => $ride->id,
                'refundable_type' => Ride::class,
                'amount' => rand(20, 150) * 1000,
                'reason' => 'Yêu cầu hoàn tiền mô phỏng cho chuyến ' . $ride->id,
                'status' => 'PENDING',
                'created_at' => now()->subHours(rand(1, 50))
            ]);
        }

        // 3. Seed User Violations
        $this->command->info('Seeding User Violations...');
        UserViolation::truncate();

        foreach ($drivers as $index => $driver) {
            UserViolation::create([
                'user_id' => $driver->id,
                'type' => 'WARNING',
                'reason' => 'Cảnh báo vi phạm quy tắc ứng xử lần ' . ($index + 1),
                'created_by' => $admin->id,
                'created_at' => now()->subDays(rand(1, 10))
            ]);
        }

        foreach ($customers as $index => $customer) {
            if ($index % 2 == 0) {
                 UserViolation::create([
                    'user_id' => $customer->id,
                    'type' => 'FINE',
                    'reason' => 'Phạt hủy chuyến quá nhiều lần',
                    'created_by' => $admin->id,
                    'created_at' => now()->subDays(rand(1, 10))
                ]);
            }
        }

        $this->command->info('Operation Support Seeding completed!');
    }
}
