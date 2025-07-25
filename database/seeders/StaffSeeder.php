<?php

namespace Database\Seeders;

use App\Models\Staff;
use App\Models\Branch;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;

class StaffSeeder extends Seeder
{
    public function run(): void
    {
        $branches = Branch::all();
        $hairCategory = ServiceCategory::where('slug', 'hair-services')->first();
        $spaCategory = ServiceCategory::where('slug', 'spa-massage')->first();
        $facialCategory = ServiceCategory::where('slug', 'facial-treatments')->first();
        $nailCategory = ServiceCategory::where('slug', 'nail-care')->first();
        $barberCategory = ServiceCategory::where('slug', 'barbershop')->first();

        $staffMembers = [
            [
                'name' => 'Sarah Johnson',
                'email' => 'sarah.johnson@ascendspa.co.ke',
                'phone' => '+254712345001',
                'specialties' => ['Hair Services', 'Facial Treatments'],
                'bio' => 'Experienced hair stylist and facial specialist with over 8 years in the beauty industry.',
                'profile_image' => null,
                'experience_years' => 8,
                'hourly_rate' => 1500.00,
                'status' => 'active',
                'services' => ['hair-services', 'facial-treatments'],
                'proficiency' => ['expert', 'expert']
            ],
            [
                'name' => 'Michael Chen',
                'email' => 'michael.chen@ascendspa.co.ke',
                'phone' => '+254712345002',
                'specialties' => ['Spa & Massage'],
                'bio' => 'Licensed massage therapist specializing in Swedish and deep tissue massage.',
                'profile_image' => null,
                'experience_years' => 6,
                'hourly_rate' => 1800.00,
                'status' => 'active',
                'services' => ['spa-massage'],
                'proficiency' => ['master']
            ],
            [
                'name' => 'Grace Wanjiku',
                'email' => 'grace.wanjiku@ascendspa.co.ke',
                'phone' => '+254712345003',
                'specialties' => ['Nail Care', 'Facial Treatments'],
                'bio' => 'Professional nail technician and aesthetician with attention to detail.',
                'profile_image' => null,
                'experience_years' => 4,
                'hourly_rate' => 1200.00,
                'status' => 'active',
                'services' => ['nail-care', 'facial-treatments'],
                'proficiency' => ['expert', 'intermediate']
            ],
            [
                'name' => 'David Kimani',
                'email' => 'david.kimani@ascendspa.co.ke',
                'phone' => '+254712345004',
                'specialties' => ['Barbershop'],
                'bio' => 'Master barber with expertise in classic and modern mens grooming.',
                'profile_image' => null,
                'experience_years' => 10,
                'hourly_rate' => 1600.00,
                'status' => 'active',
                'services' => ['barbershop'],
                'proficiency' => ['master']
            ],
            [
                'name' => 'Lisa Mwangi',
                'email' => 'lisa.mwangi@ascendspa.co.ke',
                'phone' => '+254712345005',
                'specialties' => ['Hair Services', 'Spa & Massage'],
                'bio' => 'Versatile stylist and massage therapist offering comprehensive beauty services.',
                'profile_image' => null,
                'experience_years' => 5,
                'hourly_rate' => 1400.00,
                'status' => 'active',
                'services' => ['hair-services', 'spa-massage'],
                'proficiency' => ['expert', 'intermediate']
            ],
            [
                'name' => 'James Ochieng',
                'email' => 'james.ochieng@ascendspa.co.ke',
                'phone' => '+254712345006',
                'specialties' => ['Spa & Massage', 'Facial Treatments'],
                'bio' => 'Holistic wellness practitioner specializing in therapeutic treatments.',
                'profile_image' => null,
                'experience_years' => 7,
                'hourly_rate' => 1700.00,
                'status' => 'active',
                'services' => ['spa-massage', 'facial-treatments'],
                'proficiency' => ['master', 'expert']
            ],
        ];

        foreach ($staffMembers as $staffData) {
            $serviceCategories = $staffData['services'];
            $proficiencyLevels = $staffData['proficiency'];
            unset($staffData['services'], $staffData['proficiency']);

            $staff = Staff::create($staffData);

            // Attach staff to all branches (they can work at any location)
            foreach ($branches as $branch) {
                $staff->branches()->attach($branch->id, [
                    'working_hours' => json_encode([
                        'monday' => ['start' => '09:00', 'end' => '17:00'],
                        'tuesday' => ['start' => '09:00', 'end' => '17:00'],
                        'wednesday' => ['start' => '09:00', 'end' => '17:00'],
                        'thursday' => ['start' => '09:00', 'end' => '17:00'],
                        'friday' => ['start' => '09:00', 'end' => '18:00'],
                        'saturday' => ['start' => '09:00', 'end' => '17:00'],
                        'sunday' => ['start' => '10:00', 'end' => '16:00'],
                    ]),
                    'is_primary_branch' => $branch->id === $branches->first()->id,
                ]);
            }

            // Attach services based on specialties
            foreach ($serviceCategories as $index => $categorySlug) {
                $category = ServiceCategory::where('slug', $categorySlug)->first();
                if ($category) {
                    $services = Service::where('category_id', $category->id)->get();
                    foreach ($services as $service) {
                        $staff->services()->attach($service->id, [
                            'proficiency_level' => $proficiencyLevels[$index] ?? 'intermediate'
                        ]);
                    }
                }
            }
        }
    }
}