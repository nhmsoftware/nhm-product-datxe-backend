<?php

use App\Modules\User\Model\User;
use App\Modules\User\Model\Enums\UserRole;
use App\Modules\Food\Model\FoodOrder;
use App\Modules\Ride\Model\Ride;
use App\Modules\Ride\Model\Enums\RideStatus;
use App\Modules\Ride\Model\Enums\RideType;
use App\Modules\User\Model\MerchantProfile;
use App\Modules\User\Model\DriverProfile;
use App\Modules\User\Model\Enums\DriverGroupType;
use App\Modules\User\Model\Enums\DriverStatus;
use App\Modules\User\Model\Enums\KycStatus;
use App\Modules\User\Model\Enums\VehicleColor;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin can retrieve service orders list', function () {
    $admin = User::create([
        'name' => 'Admin User',
        'phone' => '0911111111',
        'email' => 'admin@test.com',
        'password' => bcrypt('password'),
        'role' => UserRole::Admin->value,
        'is_verified' => true,
        'is_phone_verified' => true,
        'is_active' => true,
    ]);
    Sanctum::actingAs($admin);

    $customer = User::create([
        'name' => 'Customer User',
        'phone' => '0922222222',
        'email' => 'customer@test.com',
        'password' => bcrypt('password'),
        'role' => UserRole::Customer->value,
        'is_verified' => true,
        'is_phone_verified' => true,
        'is_active' => true,
    ]);

    $merchantUser = User::create([
        'name' => 'Merchant User',
        'phone' => '0933333333',
        'email' => 'merchant@test.com',
        'password' => bcrypt('password'),
        'role' => UserRole::Merchants->value,
        'is_verified' => true,
        'is_phone_verified' => true,
        'is_active' => true,
    ]);
    
    $merchantProfile = MerchantProfile::create([
        'user_id' => $merchantUser->id,
        'store_name' => 'Pizza Place',
        'store_address' => '123 Pizza St',
        'latitude' => 10.123,
        'longitude' => 106.123,
        'status' => KycStatus::Approved->value,
    ]);

    // Create a food order
    $foodOrder = FoodOrder::create([
        'customer_id' => $customer->id,
        'merchant_id' => $merchantProfile->id,
        'status' => 1, // FoodOrderStatus::PENDING
        'subtotal_price' => 100000,
        'delivery_fee' => 15000,
        'service_fee' => 2000,
        'discount_amount' => 0,
        'total_price' => 117000,
        'delivery_address' => '456 Delivery St',
        'delivery_lat' => 10.124,
        'delivery_lng' => 106.124,
        'customer_phone' => '0987654321',
    ]);

    // Create a delivery order (Ride)
    $deliveryRide = Ride::create([
        'customer_id' => $customer->id,
        'pickup_address' => '111 Pickup St',
        'pickup_lat' => 10.111,
        'pickup_lng' => 106.111,
        'destination_address' => '222 Dest St',
        'destination_lat' => 10.222,
        'destination_lng' => 106.222,
        'distance' => 5000,
        'duration' => 600,
        'vehicle_type' => 1,
        'ride_type' => RideType::DELIVERY->value,
        'status' => RideStatus::PENDING->value,
        'base_price' => 30000,
        'total_price' => 30000,
    ]);

    $response = $this->getJson('/api/v1/admin/services/orders');

    $response->assertStatus(200)
        ->assertJsonPath('success', true);

    $data = $response->json('data');

    expect(count($data))->toBeGreaterThanOrEqual(2);
});

test('admin can assign a driver to a food order', function () {
    $admin = User::create([
        'name' => 'Admin User',
        'phone' => '0911111111',
        'email' => 'admin@test.com',
        'password' => bcrypt('password'),
        'role' => UserRole::Admin->value,
        'is_verified' => true,
        'is_phone_verified' => true,
        'is_active' => true,
    ]);
    Sanctum::actingAs($admin);

    $customer = User::create([
        'name' => 'Customer User',
        'phone' => '0922222222',
        'email' => 'customer@test.com',
        'password' => bcrypt('password'),
        'role' => UserRole::Customer->value,
        'is_verified' => true,
        'is_phone_verified' => true,
        'is_active' => true,
    ]);

    $merchantUser = User::create([
        'name' => 'Merchant User',
        'phone' => '0933333333',
        'email' => 'merchant@test.com',
        'password' => bcrypt('password'),
        'role' => UserRole::Merchants->value,
        'is_verified' => true,
        'is_phone_verified' => true,
        'is_active' => true,
    ]);
    
    $merchantProfile = MerchantProfile::create([
        'user_id' => $merchantUser->id,
        'store_name' => 'Pizza Place',
        'store_address' => '123 Pizza St',
        'latitude' => 10.123,
        'longitude' => 106.123,
        'status' => KycStatus::Approved->value,
    ]);

    // Create a food order
    $foodOrder = FoodOrder::create([
        'customer_id' => $customer->id,
        'merchant_id' => $merchantProfile->id,
        'status' => 1, // FoodOrderStatus::PENDING
        'subtotal_price' => 100000,
        'delivery_fee' => 15000,
        'service_fee' => 2000,
        'discount_amount' => 0,
        'total_price' => 117000,
        'delivery_address' => '456 Delivery St',
        'delivery_lat' => 10.124,
        'delivery_lng' => 106.124,
        'customer_phone' => '0987654321',
    ]);

    // Create an internal driver
    $driverUser = User::create([
        'name' => 'Internal Driver',
        'phone' => '0944444444',
        'email' => 'driver@test.com',
        'password' => bcrypt('password'),
        'role' => UserRole::Driver->value,
        'is_verified' => true,
        'is_phone_verified' => true,
        'is_active' => true,
    ]);

    $driverProfile = DriverProfile::create([
        'user_id' => $driverUser->id,
        'full_name' => 'Internal Driver',
        'driver_group_type' => DriverGroupType::INTERNAL->value,
        'vehicle_type' => 1,
        'vehicle_color' => VehicleColor::White->value,
        'vehicle_number' => '29AB-12345',
        'vehicle_name' => 'Wave Alpha',
        'status' => DriverStatus::ACTIVE->value,
    ]);

    $response = $this->postJson('/api/v1/admin/services/orders/assign', [
        'order_id' => $foodOrder->id,
        'driver_id' => $driverUser->id,
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('success', true);

    $foodOrder->refresh();
    expect($foodOrder->ride_id)->not->toBeNull();

    $ride = Ride::find($foodOrder->ride_id);
    expect($ride->driver_id)->toBe($driverUser->id);
    expect($ride->status)->toBe(RideStatus::ACCEPTED);
});

test('admin can push a food order to pool', function () {
    $admin = User::create([
        'name' => 'Admin User',
        'phone' => '0911111111',
        'email' => 'admin@test.com',
        'password' => bcrypt('password'),
        'role' => UserRole::Admin->value,
        'is_verified' => true,
        'is_phone_verified' => true,
        'is_active' => true,
    ]);
    Sanctum::actingAs($admin);

    $customer = User::create([
        'name' => 'Customer User',
        'phone' => '0922222222',
        'email' => 'customer@test.com',
        'password' => bcrypt('password'),
        'role' => UserRole::Customer->value,
        'is_verified' => true,
        'is_phone_verified' => true,
        'is_active' => true,
    ]);

    $merchantUser = User::create([
        'name' => 'Merchant User',
        'phone' => '0933333333',
        'email' => 'merchant@test.com',
        'password' => bcrypt('password'),
        'role' => UserRole::Merchants->value,
        'is_verified' => true,
        'is_phone_verified' => true,
        'is_active' => true,
    ]);
    
    $merchantProfile = MerchantProfile::create([
        'user_id' => $merchantUser->id,
        'store_name' => 'Pizza Place',
        'store_address' => '123 Pizza St',
        'latitude' => 10.123,
        'longitude' => 106.123,
        'status' => KycStatus::Approved->value,
    ]);

    // Create a food order
    $foodOrder = FoodOrder::create([
        'customer_id' => $customer->id,
        'merchant_id' => $merchantProfile->id,
        'status' => 1, // FoodOrderStatus::PENDING
        'subtotal_price' => 100000,
        'delivery_fee' => 15000,
        'service_fee' => 2000,
        'discount_amount' => 0,
        'total_price' => 117000,
        'delivery_address' => '456 Delivery St',
        'delivery_lat' => 10.124,
        'delivery_lng' => 106.124,
        'customer_phone' => '0987654321',
    ]);

    $response = $this->postJson('/api/v1/admin/services/orders/push-to-pool', [
        'order_ids' => [$foodOrder->id],
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('success', true);

    $foodOrder->refresh();
    expect($foodOrder->ride_id)->not->toBeNull();

    $ride = Ride::find($foodOrder->ride_id);
    expect($ride->is_pushed_to_pool)->toBeTrue();
    expect($ride->status)->toBe(RideStatus::PENDING);
});
