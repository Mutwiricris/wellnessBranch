<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Branch;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class BranchManagerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all branches
        $branches = Branch::all();

        $branchManagers = [
            [
                'first_name' => 'Sarah',
                'last_name' => 'Wanjiku',
                'email' => 'sarah.manager@ascendspa.co.ke',
                'phone' => '+254700111001',
                'password' => Hash::make('password123'),
                'user_type' => 'branch_manager',
                'branch_name' => 'Ascend Spa - Westlands',
                'allergies' => 'None',
                'create_account_status' => 'active',
            ],
            [
                'first_name' => 'James',
                'last_name' => 'Muthui',
                'email' => 'james.manager@ascendspa.co.ke',
                'phone' => '+254700111002',
                'password' => Hash::make('password123'),
                'user_type' => 'branch_manager',
                'branch_name' => 'Ascend Spa - Karen',
                'allergies' => 'None',
                'create_account_status' => 'active',
            ],
            [
                'first_name' => 'Grace',
                'last_name' => 'Nyambura',
                'email' => 'grace.manager@ascendspa.co.ke',
                'phone' => '+254700111003',
                'password' => Hash::make('password123'),
                'user_type' => 'branch_manager',
                'branch_name' => 'Ascend Spa - CBD',
                'allergies' => 'None',
                'create_account_status' => 'active',
            ],
        ];

        foreach ($branchManagers as $managerData) {
            // Find the branch
            $branch = $branches->where('name', $managerData['branch_name'])->first();
            
            if ($branch) {
                // Remove branch_name from data and add branch_id
                unset($managerData['branch_name']);
                $managerData['branch_id'] = $branch->id;
                
                // Create the branch manager
                User::create($managerData);
                
                echo "Created branch manager: {$managerData['first_name']} {$managerData['last_name']} for {$branch->name}\n";
            }
        }

        // Also create a super admin for testing
        User::create([
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'email' => 'admin@ascendspa.co.ke',
            'phone' => '+254700000001',
            'password' => Hash::make('admin123'),
            'user_type' => 'admin',
            'branch_id' => null,
            'allergies' => 'None',
            'create_account_status' => 'active',
        ]);

        echo "Created super admin user\n";
    }
}
