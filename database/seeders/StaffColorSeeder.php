<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StaffColorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define a set of professional colors for staff members
        $colors = [
            '#007bff', // Blue
            '#28a745', // Green
            '#dc3545', // Red
            '#ffc107', // Yellow
            '#17a2b8', // Cyan
            '#6f42c1', // Purple
            '#fd7e14', // Orange
            '#20c997', // Teal
            '#e83e8c', // Pink
            '#6c757d', // Gray
            '#343a40', // Dark
            '#f8f9fa'  // Light
        ];

        // Get all staff members and assign colors
        $staff = \App\Models\Staff::all();
        
        foreach ($staff as $index => $staffMember) {
            $colorIndex = $index % count($colors);
            $staffMember->update([
                'color' => $colors[$colorIndex]
            ]);
        }

        $this->command->info('Staff colors updated successfully!');
    }
}
