<?php

namespace Database\Seeders;

use App\Modules\User\Model\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        $this->call([
            AdminUserSeeder::class,
            AirportSeeder::class,
            VehicleTypeSeeder::class,
            SubscriptionPackageSeeder::class,
        ]);
    }
}
