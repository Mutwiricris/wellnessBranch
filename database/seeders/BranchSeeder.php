<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Service;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $branches = [
            [
                'name' => 'Ascend Spa - Westlands',
                'address' => 'Westlands Shopping Centre, Ring Road, Nairobi',
                'phone' => '+254700123456',
                'email' => 'westlands@ascendspa.co.ke',
                'working_hours' => [
                    'monday' => ['open' => '09:00', 'close' => '18:00'],
                    'tuesday' => ['open' => '09:00', 'close' => '18:00'],
                    'wednesday' => ['open' => '09:00', 'close' => '18:00'],
                    'thursday' => ['open' => '09:00', 'close' => '18:00'],
                    'friday' => ['open' => '09:00', 'close' => '20:00'],
                    'saturday' => ['open' => '08:00', 'close' => '19:00'],
                    'sunday' => ['open' => '10:00', 'close' => '17:00'],
                ],
                'timezone' => 'Africa/Nairobi',
                'status' => 'active',
            ],
            [
                'name' => 'Ascend Spa - Karen',
                'address' => 'Karen Shopping Centre, Karen Road, Nairobi',
                'phone' => '+254700123457',
                'email' => 'karen@ascendspa.co.ke',
                'working_hours' => [
                    'monday' => ['open' => '08:30', 'close' => '17:30'],
                    'tuesday' => ['open' => '08:30', 'close' => '17:30'],
                    'wednesday' => ['open' => '08:30', 'close' => '17:30'],
                    'thursday' => ['open' => '08:30', 'close' => '17:30'],
                    'friday' => ['open' => '08:30', 'close' => '19:00'],
                    'saturday' => ['open' => '08:00', 'close' => '18:00'],
                    'sunday' => ['open' => '09:30', 'close' => '16:30'],
                ],
                'timezone' => 'Africa/Nairobi',
                'status' => 'active',
            ],
            [
                'name' => 'Ascend Spa - CBD',
                'address' => 'Kencom House, Moi Avenue, Nairobi CBD',
                'phone' => '+254700123458',
                'email' => 'cbd@ascendspa.co.ke',
                'working_hours' => [
                    'monday' => ['open' => '08:00', 'close' => '18:00'],
                    'tuesday' => ['open' => '08:00', 'close' => '18:00'],
                    'wednesday' => ['open' => '08:00', 'close' => '18:00'],
                    'thursday' => ['open' => '08:00', 'close' => '18:00'],
                    'friday' => ['open' => '08:00', 'close' => '19:00'],
                    'saturday' => ['open' => '09:00', 'close' => '17:00'],
                    'sunday' => ['open' => '10:00', 'close' => '16:00'],
                ],
                'timezone' => 'Africa/Nairobi',
                'status' => 'active',
            ],
        ];

        foreach ($branches as $branchData) {
            $branch = Branch::create($branchData);

            // Attach all services to each branch
            $services = Service::all();
            foreach ($services as $service) {
                $branch->services()->attach($service->id, [
                    'is_available' => true,
                    'custom_price' => null, // Use default service price
                ]);
            }
        }
    }
}