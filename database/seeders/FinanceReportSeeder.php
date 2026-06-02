<?php

namespace Database\Seeders;

use App\Modules\Finance\Model\CommissionRule;
use App\Modules\Finance\Model\DriverSubscription;
use App\Modules\Finance\Model\Enums\CommissionScope;
use App\Modules\Finance\Model\Enums\CommissionServiceType;
use App\Modules\Finance\Model\Enums\CommissionTargetType;
use App\Modules\Finance\Model\SubscriptionPackage;
use App\Modules\Ride\Model\Enums\RideStatus;
use App\Modules\Ride\Model\Enums\RideType;
use App\Modules\Ride\Model\Enums\VehicleType;
use App\Modules\Ride\Model\Ride;
use App\Modules\User\Model\Enums\UserRole;
use App\Modules\User\Model\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * FinanceReportSeeder
 * 
 * Tạo dữ liệu fake phong phú cho Dashboard Báo cáo Tài chính:
 * - Rides đã hoàn thành (12 tháng, theo từng loại dịch vụ)
 * - Commission Rules (Chuyến xe, Ăn uống, Giao hàng)
 * - Driver Subscriptions (Ngày, Tuần, Tháng)
 */
class FinanceReportSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🚀 Bắt đầu tạo dữ liệu Báo cáo Tài chính...');

        // ── 1. Tạo Users cần thiết ──
        $customer = $this->ensureCustomer();
        $drivers  = $this->ensureDrivers();

        // ── 2. Tạo Commission Rules ──
        $this->seedCommissionRules();

        // ── 3. Tạo Rides 12 tháng ──
        $this->seedRides($customer, $drivers);

        // ── 4. Tạo Subscription Packages & Driver Subscriptions ──
        $this->seedSubscriptions($drivers);

        $this->command->info('');
        $this->command->info('✅ Hoàn thành! Dữ liệu tài chính đã sẵn sàng.');
        $this->command->info('   👉 Truy cập: /finance/driver-summary → Mở Dashboard Phân tích');
    }

    // ─────────────────────────────────────────────────────────────────
    // Helpers: Users
    // ─────────────────────────────────────────────────────────────────

    private function ensureCustomer(): User
    {
        $customer = User::where('role', UserRole::Customer)->first();
        if (!$customer) {
            $customer = User::create([
                'phone'             => '0901234567',
                'email'             => 'customer@nhm-datxe.com',
                'password'          => Hash::make('password'),
                'role'              => UserRole::Customer,
                'is_active'         => true,
                'is_verified'       => true,
                'is_phone_verified' => true,
            ]);
            $this->command->info("  ✔ Tạo customer: {$customer->email}");
        }
        return $customer;
    }

    private function ensureDrivers(): array
    {
        $driverData = [
            ['phone' => '0911222001', 'email' => 'driver.report01@nhm-datxe.com', 'name' => 'Nguyễn Tài Chính'],
            ['phone' => '0911222002', 'email' => 'driver.report02@nhm-datxe.com', 'name' => 'Trần Văn Báo'],
            ['phone' => '0911222003', 'email' => 'driver.report03@nhm-datxe.com', 'name' => 'Lê Thị Cáo'],
            ['phone' => '0911222004', 'email' => 'driver.report04@nhm-datxe.com', 'name' => 'Phạm Minh Tuấn'],
            ['phone' => '0911222005', 'email' => 'driver.report05@nhm-datxe.com', 'name' => 'Võ Đức Hùng'],
        ];

        $drivers = [];
        foreach ($driverData as $d) {
            $user = User::where('phone', $d['phone'])->first();
            if (!$user) {
                $user = User::create([
                    'phone'             => $d['phone'],
                    'email'             => $d['email'],
                    'password'          => Hash::make('Driver@123'),
                    'role'              => UserRole::Driver,
                    'is_active'         => true,
                    'is_verified'       => true,
                    'is_phone_verified' => true,
                ]);
                $this->command->info("  ✔ Tạo driver: {$d['name']}");
            }
            $drivers[] = $user;
        }
        return $drivers;
    }

    // ─────────────────────────────────────────────────────────────────
    // Commission Rules
    // ─────────────────────────────────────────────────────────────────

    private function seedCommissionRules(): void
    {
        $this->command->info('  📋 Tạo Commission Rules...');

        $rules = [
            [
                'name'            => 'Hoa hồng Chuyến xe - Toàn quốc',
                'target_type'     => CommissionTargetType::DRIVER,
                'service_type'    => CommissionServiceType::RIDE,
                'scope'           => CommissionScope::SYSTEM,
                'commission_rate' => 12.0,
                'min_commission'  => 5000,
                'max_commission'  => null,
                'is_active'       => true,
                'effective_from'  => now()->subYear(),
                'effective_to'    => null,
            ],
            [
                'name'            => 'Hoa hồng Ăn uống - Toàn quốc',
                'target_type'     => CommissionTargetType::DRIVER,
                'service_type'    => CommissionServiceType::FOOD,
                'scope'           => CommissionScope::SYSTEM,
                'commission_rate' => 18.0,
                'min_commission'  => 3000,
                'max_commission'  => null,
                'is_active'       => true,
                'effective_from'  => now()->subYear(),
                'effective_to'    => null,
            ],
            [
                'name'            => 'Hoa hồng Giao hàng - Toàn quốc',
                'target_type'     => CommissionTargetType::DRIVER,
                'service_type'    => CommissionServiceType::DELIVERY,
                'scope'           => CommissionScope::SYSTEM,
                'commission_rate' => 15.0,
                'min_commission'  => 2000,
                'max_commission'  => null,
                'is_active'       => true,
                'effective_from'  => now()->subYear(),
                'effective_to'    => null,
            ],
            [
                'name'            => 'Hoa hồng Merchant Ăn uống',
                'target_type'     => CommissionTargetType::MERCHANT,
                'service_type'    => CommissionServiceType::FOOD,
                'scope'           => CommissionScope::SYSTEM,
                'commission_rate' => 25.0,
                'min_commission'  => null,
                'max_commission'  => null,
                'is_active'       => true,
                'effective_from'  => now()->subYear(),
                'effective_to'    => null,
            ],
        ];

        foreach ($rules as $rule) {
            // Tránh tạo trùng nếu đã seed trước
            $exists = CommissionRule::where('name', $rule['name'])->exists();
            if (!$exists) {
                CommissionRule::create($rule);
            }
        }

        $this->command->info('    ✔ Commission Rules đã tạo: ' . count($rules) . ' quy tắc');
    }

    // ─────────────────────────────────────────────────────────────────
    // Rides — 12 tháng
    // ─────────────────────────────────────────────────────────────────

    private function seedRides(User $customer, array $drivers): void
    {
        $this->command->info('  🚗 Tạo Rides 12 tháng...');

        $addressPairs = [
            ['pickup' => '12 Trần Hưng Đạo, Hoàn Kiếm, Hà Nội', 'dest' => 'Nội Bài Airport, Sóc Sơn, Hà Nội'],
            ['pickup' => '45 Lê Duẩn, Đống Đa, Hà Nội', 'dest' => '79 Nguyễn Chí Thanh, Ba Đình, Hà Nội'],
            ['pickup' => '1 Hồ Hoàn Kiếm, Hoàn Kiếm, Hà Nội', 'dest' => 'Vincom Mega Mall Royal City, Thanh Xuân'],
            ['pickup' => '200 Trần Duy Hưng, Cầu Giấy, Hà Nội', 'dest' => 'Keangnam Landmark, Nam Từ Liêm'],
            ['pickup' => '8 Lê Thái Tổ, Hoàn Kiếm, Hà Nội', 'dest' => 'Big C Thăng Long, Nam Từ Liêm'],
        ];

        $vehicleTypes = [VehicleType::BIKE, VehicleType::CAR_4_SEATS, VehicleType::CAR_7_SEATS];

        // Tạo config GMV theo tháng (12 tháng gần nhất, tháng xa nhất ít hơn)
        $monthlyConfig = [
            1  => ['count' => 35,  'base_price_range' => [40000,  90000]],
            2  => ['count' => 28,  'base_price_range' => [40000,  85000]],
            3  => ['count' => 42,  'base_price_range' => [45000,  95000]],
            4  => ['count' => 50,  'base_price_range' => [45000, 100000]],
            5  => ['count' => 48,  'base_price_range' => [50000, 110000]],
            6  => ['count' => 55,  'base_price_range' => [50000, 120000]],
            7  => ['count' => 60,  'base_price_range' => [55000, 125000]],
            8  => ['count' => 58,  'base_price_range' => [55000, 130000]],
            9  => ['count' => 65,  'base_price_range' => [60000, 135000]],
            10 => ['count' => 70,  'base_price_range' => [60000, 140000]],
            11 => ['count' => 68,  'base_price_range' => [65000, 145000]],
            12 => ['count' => 80,  'base_price_range' => [65000, 150000]],
        ];

        $totalCreated = 0;

        for ($month = 1; $month <= 12; $month++) {
            $cfg      = $monthlyConfig[$month];
            $year     = now()->year;
            $monthDate = now()->setYear($year)->setMonth($month)->startOfMonth();

            // Bỏ qua tháng trong tương lai
            if ($monthDate->isFuture()) {
                continue;
            }

            for ($i = 0; $i < $cfg['count']; $i++) {
                $driver  = $drivers[array_rand($drivers)];
                $addr    = $addressPairs[array_rand($addressPairs)];
                $vType   = $vehicleTypes[array_rand($vehicleTypes)];
                $baseP   = rand($cfg['base_price_range'][0], $cfg['base_price_range'][1]);
                $distP   = rand(10000, 50000);
                $totalP  = $baseP + $distP;
                $svcFee  = round($totalP * 0.12); // 12% commission
                $earning = $totalP - $svcFee;

                // Random ngày trong tháng
                $daysInMonth = $monthDate->daysInMonth;
                $day = rand(1, min($daysInMonth, $monthDate->isCurrentMonth() ? now()->day : $daysInMonth));
                $createdAt = $monthDate->copy()->setDay($day)->addHours(rand(7, 22))->addMinutes(rand(0, 59));

                Ride::create([
                    'customer_id'         => $customer->id,
                    'driver_id'           => $driver->id,
                    'pickup_address'      => $addr['pickup'],
                    'pickup_lat'          => 21.0285 + (rand(-100, 100) / 10000),
                    'pickup_lng'          => 105.8542 + (rand(-100, 100) / 10000),
                    'destination_address' => $addr['dest'],
                    'destination_lat'     => 21.0485 + (rand(-100, 100) / 10000),
                    'destination_lng'     => 105.8742 + (rand(-100, 100) / 10000),
                    'distance'            => rand(3000, 25000),
                    'duration'            => rand(600, 3600),
                    'vehicle_type'        => $vType,
                    'ride_type'           => RideType::CITY,
                    'status'              => RideStatus::COMPLETED,
                    'base_price'          => $baseP,
                    'distance_price'      => $distP,
                    'total_price'         => $totalP,
                    'service_fee'         => $svcFee,
                    'driver_earnings'     => $earning,
                    'is_paid'             => true,
                    'started_at'          => $createdAt->copy()->addMinutes(rand(5, 15)),
                    'completed_at'        => $createdAt->copy()->addMinutes(rand(20, 60)),
                    'created_at'          => $createdAt,
                    'updated_at'          => $createdAt,
                ]);

                $totalCreated++;
            }

            $this->command->info("    ✔ Tháng {$month}/{$year}: {$cfg['count']} rides");
        }

        $this->command->info("    ✅ Tổng cộng: {$totalCreated} rides đã tạo");
    }

    // ─────────────────────────────────────────────────────────────────
    // Subscription Packages + Driver Subscriptions
    // ─────────────────────────────────────────────────────────────────

    private function seedSubscriptions(array $drivers): void
    {
        $this->command->info('  📦 Tạo Subscription Packages & Driver Subscriptions...');

        // Đảm bảo có các gói cơ bản
        $packages = [
            [
                'name'                          => 'Gói Ngày',
                'package_type'                  => 1,
                'description'                   => 'Miễn hoa hồng 12% trong 1 ngày',
                'price'                         => 15000,
                'duration_days'                 => 1,
                'service_fee_reduction_percent' => 100,
                'is_active'                     => true,
            ],
            [
                'name'                          => 'Gói Tuần',
                'package_type'                  => 2,
                'description'                   => 'Giảm 80% hoa hồng trong 7 ngày',
                'price'                         => 79000,
                'duration_days'                 => 7,
                'service_fee_reduction_percent' => 80,
                'is_active'                     => true,
            ],
            [
                'name'                          => 'Gói Tháng',
                'package_type'                  => 3,
                'description'                   => 'Giảm 70% hoa hồng trong 30 ngày',
                'price'                         => 199000,
                'duration_days'                 => 30,
                'service_fee_reduction_percent' => 70,
                'is_active'                     => true,
            ],
        ];

        $packageModels = [];
        foreach ($packages as $pkg) {
            $packageModel = SubscriptionPackage::firstOrCreate(
                ['name' => $pkg['name']],
                $pkg
            );
            $packageModels[] = $packageModel;
        }

        // Tạo DriverSubscriptions cho 12 tháng
        $subscriptionStats = [1 => 0, 2 => 0, 3 => 0]; // package_type => count
        $monthlySubCounts  = [
            1 => 15, 2 => 12, 3 => 18, 4 => 22, 5 => 25, 6 => 28,
            7 => 32, 8 => 30, 9 => 35, 10 => 38, 11 => 36, 12 => 42,
        ];

        for ($month = 1; $month <= 12; $month++) {
            $year      = now()->year;
            $monthDate = now()->setYear($year)->setMonth($month)->startOfMonth();
            if ($monthDate->isFuture()) {
                continue;
            }

            $count = $monthlySubCounts[$month];

            for ($i = 0; $i < $count; $i++) {
                $driver  = $drivers[array_rand($drivers)];
                $package = $packageModels[array_rand($packageModels)];
                $day     = rand(1, min($monthDate->daysInMonth, $monthDate->isCurrentMonth() ? now()->day : $monthDate->daysInMonth));
                $startAt = $monthDate->copy()->setDay($day)->addHours(rand(8, 20));
                $expAt   = $startAt->copy()->addDays($package->duration_days);

                DriverSubscription::create([
                    'driver_id'  => $driver->id,
                    'package_id' => $package->id,
                    'started_at' => $startAt,
                    'expires_at' => $expAt,
                    'status'     => 'active',
                    'price_paid' => $package->price,
                    'created_at' => $startAt,
                    'updated_at' => $startAt,
                ]);

                $subscriptionStats[$package->package_type] = ($subscriptionStats[$package->package_type] ?? 0) + 1;
            }
        }

        $this->command->info('    ✔ Subscriptions đã tạo:');
        foreach ($packageModels as $pkg) {
            $count = $subscriptionStats[$pkg->package_type] ?? 0;
            $this->command->info("       - {$pkg->name}: {$count} đăng ký");
        }
    }
}
