<?php

namespace Database\Seeders;

use App\Models\Staff;
use App\Models\Branch;
use App\Models\StaffSchedule;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class StaffScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $staff = Staff::all();
        $branches = Branch::all();
        
        // Create schedules for the next 30 days
        $startDate = Carbon::now();
        $endDate = $startDate->clone()->addDays(30);

        foreach ($staff as $staffMember) {
            foreach ($branches as $branch) {
                // Only create schedules for branches this staff member is attached to
                if (!$staffMember->branches->contains($branch->id)) {
                    continue;
                }

                for ($date = $startDate->clone(); $date <= $endDate; $date->addDay()) {
                    $dayOfWeek = strtolower($date->format('l'));
                    
                    // Skip some random days to simulate real availability
                    if (rand(1, 10) <= 2) { // 20% chance of being unavailable
                        continue;
                    }

                    // Get working hours based on day of week
                    $workingHours = $this->getWorkingHoursForDay($dayOfWeek);
                    
                    if ($workingHours) {
                        StaffSchedule::create([
                            'staff_id' => $staffMember->id,
                            'branch_id' => $branch->id,
                            'date' => $date->toDateString(),
                            'start_time' => $workingHours['start'],
                            'end_time' => $workingHours['end'],
                            'is_available' => true,
                            'break_start' => $workingHours['break_start'] ?? null,
                            'break_end' => $workingHours['break_end'] ?? null,
                            'notes' => null,
                        ]);
                    }
                }
            }
        }
    }

    private function getWorkingHoursForDay(string $dayOfWeek): ?array
    {
        $schedules = [
            'monday' => [
                'start' => '09:00',
                'end' => '17:00',
                'break_start' => '12:30',
                'break_end' => '13:30'
            ],
            'tuesday' => [
                'start' => '09:00',
                'end' => '17:00',
                'break_start' => '12:30',
                'break_end' => '13:30'
            ],
            'wednesday' => [
                'start' => '09:00',
                'end' => '17:00',
                'break_start' => '12:30',
                'break_end' => '13:30'
            ],
            'thursday' => [
                'start' => '09:00',
                'end' => '17:00',
                'break_start' => '12:30',
                'break_end' => '13:30'
            ],
            'friday' => [
                'start' => '09:00',
                'end' => '18:00',
                'break_start' => '13:00',
                'break_end' => '14:00'
            ],
            'saturday' => [
                'start' => '09:00',
                'end' => '17:00',
                'break_start' => '13:00',
                'break_end' => '13:30'
            ],
            'sunday' => [
                'start' => '10:00',
                'end' => '16:00',
                'break_start' => '13:00',
                'break_end' => '13:30'
            ],
        ];

        return $schedules[$dayOfWeek] ?? null;
    }
}