<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\ServiceCategory;
use App\Models\Service;
use App\Models\Branch;
use App\Models\Staff;
use App\Models\StaffSchedule;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            ServiceCategorySeeder::class,
            ServiceSeeder::class,
            BranchSeeder::class,
            BranchManagerSeeder::class,
            StaffSeeder::class,
            StaffScheduleSeeder::class,
        ]);

        // Create test user
        User::factory()->create([
            'email' => 'test@example.com',
            'first_name' => 'Test',
            'last_name' => 'User',
            'phone' => '+254712345678',
            'allergies' => 'None',
            'create_account_status' => 'active'
        ]);
    }
}
