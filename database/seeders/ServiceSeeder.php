<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $hairCategory = ServiceCategory::where('slug', 'hair-services')->first();
        $spaCategory = ServiceCategory::where('slug', 'spa-massage')->first();
        $facialCategory = ServiceCategory::where('slug', 'facial-treatments')->first();
        $nailCategory = ServiceCategory::where('slug', 'nail-care')->first();
        $barberCategory = ServiceCategory::where('slug', 'barbershop')->first();

        $services = [
            // Hair Services
            [
                'category_id' => $hairCategory->id,
                'name' => 'Hair Cut & Style',
                'description' => 'Professional haircut with styling',
                'price' => 2500.00,
                'duration_minutes' => 60,
                'buffer_time_minutes' => 15,
                'max_advance_booking_days' => 30,
                'requires_consultation' => false,
                'is_couple_service' => false,
                'status' => 'active',
            ],
            [
                'category_id' => $hairCategory->id,
                'name' => 'Hair Coloring',
                'description' => 'Professional hair coloring service',
                'price' => 4500.00,
                'duration_minutes' => 120,
                'buffer_time_minutes' => 30,
                'max_advance_booking_days' => 21,
                'requires_consultation' => true,
                'is_couple_service' => false,
                'status' => 'active',
            ],
            [
                'category_id' => $hairCategory->id,
                'name' => 'Hair Treatment',
                'description' => 'Deep conditioning and treatment',
                'price' => 3000.00,
                'duration_minutes' => 90,
                'buffer_time_minutes' => 15,
                'max_advance_booking_days' => 30,
                'requires_consultation' => false,
                'is_couple_service' => false,
                'status' => 'active',
            ],

            // Spa & Massage
            [
                'category_id' => $spaCategory->id,
                'name' => 'Swedish Massage',
                'description' => 'Relaxing full body massage',
                'price' => 3500.00,
                'duration_minutes' => 60,
                'buffer_time_minutes' => 15,
                'max_advance_booking_days' => 14,
                'requires_consultation' => false,
                'is_couple_service' => true,
                'status' => 'active',
            ],
            [
                'category_id' => $spaCategory->id,
                'name' => 'Deep Tissue Massage',
                'description' => 'Therapeutic deep tissue massage',
                'price' => 4000.00,
                'duration_minutes' => 75,
                'buffer_time_minutes' => 15,
                'max_advance_booking_days' => 14,
                'requires_consultation' => true,
                'is_couple_service' => true,
                'status' => 'active',
            ],
            [
                'category_id' => $spaCategory->id,
                'name' => 'Hot Stone Massage',
                'description' => 'Luxurious hot stone therapy',
                'price' => 5000.00,
                'duration_minutes' => 90,
                'buffer_time_minutes' => 20,
                'max_advance_booking_days' => 21,
                'requires_consultation' => false,
                'is_couple_service' => true,
                'status' => 'active',
            ],

            // Facial Treatments
            [
                'category_id' => $facialCategory->id,
                'name' => 'Classic Facial',
                'description' => 'Basic cleansing and moisturizing facial',
                'price' => 2000.00,
                'duration_minutes' => 45,
                'buffer_time_minutes' => 15,
                'max_advance_booking_days' => 30,
                'requires_consultation' => false,
                'is_couple_service' => false,
                'status' => 'active',
            ],
            [
                'category_id' => $facialCategory->id,
                'name' => 'Anti-Aging Facial',
                'description' => 'Advanced anti-aging treatment',
                'price' => 3500.00,
                'duration_minutes' => 75,
                'buffer_time_minutes' => 15,
                'max_advance_booking_days' => 21,
                'requires_consultation' => true,
                'is_couple_service' => false,
                'status' => 'active',
            ],
            [
                'category_id' => $facialCategory->id,
                'name' => 'Hydrating Facial',
                'description' => 'Deep hydration and nourishment',
                'price' => 2800.00,
                'duration_minutes' => 60,
                'buffer_time_minutes' => 15,
                'max_advance_booking_days' => 30,
                'requires_consultation' => false,
                'is_couple_service' => false,
                'status' => 'active',
            ],

            // Nail Care
            [
                'category_id' => $nailCategory->id,
                'name' => 'Manicure',
                'description' => 'Professional nail care and polish',
                'price' => 1500.00,
                'duration_minutes' => 45,
                'buffer_time_minutes' => 15,
                'max_advance_booking_days' => 14,
                'requires_consultation' => false,
                'is_couple_service' => false,
                'status' => 'active',
            ],
            [
                'category_id' => $nailCategory->id,
                'name' => 'Pedicure',
                'description' => 'Complete foot care and polish',
                'price' => 1800.00,
                'duration_minutes' => 60,
                'buffer_time_minutes' => 15,
                'max_advance_booking_days' => 14,
                'requires_consultation' => false,
                'is_couple_service' => false,
                'status' => 'active',
            ],
            [
                'category_id' => $nailCategory->id,
                'name' => 'Gel Manicure',
                'description' => 'Long-lasting gel polish manicure',
                'price' => 2200.00,
                'duration_minutes' => 60,
                'buffer_time_minutes' => 15,
                'max_advance_booking_days' => 21,
                'requires_consultation' => false,
                'is_couple_service' => false,
                'status' => 'active',
            ],

            // Barbershop
            [
                'category_id' => $barberCategory->id,
                'name' => "Men's Haircut",
                'description' => 'Classic barbershop haircut',
                'price' => 1800.00,
                'duration_minutes' => 45,
                'buffer_time_minutes' => 15,
                'max_advance_booking_days' => 14,
                'requires_consultation' => false,
                'is_couple_service' => false,
                'status' => 'active',
            ],
            [
                'category_id' => $barberCategory->id,
                'name' => 'Beard Trim',
                'description' => 'Professional beard trimming and styling',
                'price' => 1200.00,
                'duration_minutes' => 30,
                'buffer_time_minutes' => 15,
                'max_advance_booking_days' => 14,
                'requires_consultation' => false,
                'is_couple_service' => false,
                'status' => 'active',
            ],
            [
                'category_id' => $barberCategory->id,
                'name' => 'Haircut & Beard Package',
                'description' => 'Complete grooming package',
                'price' => 2500.00,
                'duration_minutes' => 75,
                'buffer_time_minutes' => 15,
                'max_advance_booking_days' => 14,
                'requires_consultation' => false,
                'is_couple_service' => false,
                'status' => 'active',
            ],
        ];

        foreach ($services as $service) {
            Service::create($service);
        }
    }
}