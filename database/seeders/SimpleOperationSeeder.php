<?php

namespace Database\Seeders;

use App\Modules\Complaint\Model\Complaint;
use App\Modules\Complaint\Model\Enums\ComplaintStatus;
use App\Modules\Finance\Model\RefundRequest;
use App\Modules\Finance\Model\Enums\RefundStatus;
use App\Modules\RiskManagement\Model\UserViolation;
use App\Modules\User\Model\User;
use App\Modules\Ride\Model\Ride;
use App\Modules\User\Model\Enums\UserRole;
use App\Modules\Ride\Model\Enums\RideStatus;
use App\Modules\Ride\Model\Enums\RideType;
use App\Modules\Ride\Model\Enums\VehicleType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SimpleOperationSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting Simple Operation Seeder...');

        // 1. Ensure Admin exists
        $admin = User::where('role', UserRole::Admin)->first();
        if (!$admin) {
            $admin = User::create([
                'id' => (string) Str::orderedUuid(),
                'phone' => '0999888777',
                'email' => 'admin@nhm.vn',
                'password' => Hash::make('password'),
                'role' => UserRole::Admin,
                'is_active' => true,
                'is_verified' => true,
            ]);
        }

        // 2. Ensure Customer exists
        $customer = User::where('role', UserRole::Customer)->first();
        if (!$customer) {
            $customer = User::create([
                'id' => (string) Str::orderedUuid(),
                'phone' => '0901234567',
                'email' => 'customer@test.com',
                'password' => Hash::make('password'),
                'role' => UserRole::Customer,
                'is_active' => true,
                'is_verified' => true,
            ]);
        }

        // 3. Ensure Driver exists
        $driver = User::where('role', UserRole::Driver)->first();
        if (!$driver) {
            $driver = User::create([
                'id' => (string) Str::orderedUuid(),
                'phone' => '0912345678',
                'email' => 'driver@test.com',
                'password' => Hash::make('password'),
                'role' => UserRole::Driver,
                'is_active' => true,
                'is_verified' => true,
            ]);
        }

        // 4. Ensure some Rides exist
        $ride = Ride::first();
        if (!$ride) {
            $ride = Ride::create([
                'id' => (string) Str::orderedUuid(),
                'customer_id' => $customer->id,
                'driver_id' => $driver->id,
                'pickup_address' => '123 Nguyễn Huệ, Quận 1, TP.HCM',
                'pickup_lat' => 10.776,
                'pickup_lng' => 106.701,
                'destination_address' => 'Landmark 81, Bình Thạnh, TP.HCM',
                'destination_lat' => 10.795,
                'destination_lng' => 106.722,
                'distance' => 5000,
                'duration' => 900,
                'vehicle_type' => VehicleType::CAR_4_SEATS,
                'ride_type' => RideType::NORMAL,
                'status' => RideStatus::COMPLETED,
                'base_price' => 50000,
                'total_price' => 75000,
                'is_paid' => true,
                'started_at' => now()->subMinutes(30),
                'completed_at' => now()->subMinutes(15),
            ]);
        }

        // 5. Create Complaints
        $this->command->info('Creating Complaints...');
        Complaint::create([
            'sender_id' => $customer->id,
            'complaintable_id' => $ride->id,
            'complaintable_type' => Ride::class,
            'type' => 'ATTITUDE',
            'content' => 'Tài xế có thái độ không tốt, nói chuyện điện thoại quá nhiều trong lúc lái xe.',
            'evidence' => ['https://placehold.co/600x400?text=Evidence+1', 'https://placehold.co/600x400?text=Evidence+2'],
            'status' => ComplaintStatus::PENDING,
        ]);

        Complaint::create([
            'sender_id' => $customer->id,
            'complaintable_id' => $ride->id,
            'complaintable_type' => Ride::class,
            'type' => 'OVERCHARGE',
            'content' => 'Tôi bị thu thêm tiền mặt dù đã thanh toán qua ví.',
            'status' => ComplaintStatus::PENDING,
        ]);

        // 6. Create Refund Requests
        $this->command->info('Creating Refund Requests...');
        RefundRequest::create([
            'customer_id' => $customer->id,
            'refundable_id' => $ride->id,
            'refundable_type' => Ride::class,
            'amount' => 25000,
            'reason' => 'Tài xế đi sai đường làm tăng quãng đường so với dự kiến.',
            'status' => RefundStatus::PENDING,
        ]);

        // 7. Create Violations
        $this->command->info('Creating Violations...');
        UserViolation::create([
            'user_id' => $driver->id,
            'type' => 'WARNING',
            'reason' => 'Cảnh báo về thái độ phục vụ khách hàng.',
            'created_by' => $admin->id,
        ]);

        $this->command->info('Simple Operation Seeder finished!');
    }
}
