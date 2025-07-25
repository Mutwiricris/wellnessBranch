<?php

namespace Database\Seeders;

use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;

class ServiceCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Hair Services',
                'icon' => '💇',
                'slug' => 'hair-services',
                'sort_order' => 1,
                'status' => true,
            ],
            [
                'name' => 'Spa & Massage',
                'icon' => '💆',
                'slug' => 'spa-massage',
                'sort_order' => 2,
                'status' => true,
            ],
            [
                'name' => 'Facial Treatments',
                'icon' => '✨',
                'slug' => 'facial-treatments',
                'sort_order' => 3,
                'status' => true,
            ],
            [
                'name' => 'Nail Care',
                'icon' => '💅',
                'slug' => 'nail-care',
                'sort_order' => 4,
                'status' => true,
            ],
            [
                'name' => 'Barbershop',
                'icon' => '✂️',
                'slug' => 'barbershop',
                'sort_order' => 5,
                'status' => true,
            ],
        ];

        foreach ($categories as $category) {
            ServiceCategory::create($category);
        }
    }
}