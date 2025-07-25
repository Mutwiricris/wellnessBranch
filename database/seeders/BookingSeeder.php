<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\User;
use App\Models\Branch;
use App\Models\Service;
use App\Models\Staff;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class BookingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branches = Branch::all();
        $services = Service::all();
        
        foreach ($branches as $branch) {
            // Get staff for this branch
            $branchStaff = Staff::whereHas('branches', function($q) use ($branch) {
                $q->where('branch_id', $branch->id);
            })->get();
            
            // Create sample customers for this branch
            $customers = [];
            for ($i = 0; $i < 5; $i++) {
                $customers[] = User::create([
                    'first_name' => fake()->firstName(),
                    'last_name' => fake()->lastName(),
                    'email' => fake()->unique()->email(),
                    'phone' => '+254' . fake()->numberBetween(700000000, 799999999),
                    'user_type' => 'user',
                    'allergies' => fake()->randomElement(['None', 'Nuts', 'Shellfish', 'Pollen', 'Dairy']),
                    'create_account_status' => 'active',
                ]);
            }
            
            // Create bookings for today, yesterday, tomorrow
            $dates = [
                today()->subDay(), // Yesterday
                today(),           // Today  
                today()->addDay(), // Tomorrow
                today()->addDays(2), // Day after tomorrow
            ];
            
            foreach ($dates as $date) {
                $bookingsCount = fake()->numberBetween(3, 8);
                
                for ($i = 0; $i < $bookingsCount; $i++) {
                    $service = $services->random();
                    $customer = fake()->randomElement($customers);
                    $staff = $branchStaff->isNotEmpty() ? $branchStaff->random() : null;
                    
                    // Generate time slots
                    $startHour = fake()->numberBetween(9, 16); // 9 AM to 4 PM
                    $startMinute = fake()->randomElement([0, 30]);
                    $startTime = sprintf('%02d:%02d', $startHour, $startMinute);
                    
                    $endTime = Carbon::createFromFormat('H:i', $startTime)
                        ->addMinutes($service->duration_minutes)
                        ->format('H:i');
                    
                    // Determine status based on date
                    if ($date->isPast()) {
                        $status = fake()->randomElement(['completed', 'cancelled', 'no_show']);
                    } elseif ($date->isToday()) {
                        $status = fake()->randomElement(['pending', 'confirmed', 'in_progress', 'completed']);
                    } else {
                        $status = fake()->randomElement(['pending', 'confirmed']);
                    }
                    
                    $booking = Booking::create([
                        'branch_id' => $branch->id,
                        'service_id' => $service->id,
                        'client_id' => $customer->id,
                        'staff_id' => $staff?->id,
                        'appointment_date' => $date,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'status' => $status,
                        'total_amount' => $service->price,
                        'payment_status' => $status === 'completed' ? 'paid' : 'pending',
                        'payment_method' => fake()->randomElement(['cash', 'mpesa', 'card']),
                        'notes' => fake()->optional(0.3)->sentence(),
                        'confirmed_at' => in_array($status, ['confirmed', 'in_progress', 'completed']) ? now() : null,
                        'cancelled_at' => $status === 'cancelled' ? now() : null,
                    ]);
                    
                    echo "Created booking {$booking->booking_reference} for {$branch->name} on {$date->format('Y-m-d')}\n";
                }
            }
        }
    }
}
