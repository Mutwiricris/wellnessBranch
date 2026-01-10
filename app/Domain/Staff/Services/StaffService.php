<?php

namespace App\Domain\Staff\Services;

use App\Domain\Staff\Repositories\StaffRepositoryInterface;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Exception;

class StaffService
{
    public function __construct(
        private StaffRepositoryInterface $staffRepository
    ) {}

    /**
     * Create a new staff member
     */
    public function createStaff(array $data): Staff
    {
        DB::beginTransaction();

        try {
            // Create the staff member
            $staff = $this->staffRepository->create($data);

            // Attach branches if provided
            if (isset($data['branches'])) {
                $staff->branches()->attach($data['branches']);
            }

            // Attach services if provided
            if (isset($data['services'])) {
                $staff->services()->attach($data['services']);
            }

            DB::commit();

            return $staff->load(['branches', 'services']);

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update staff member
     */
    public function updateStaff(int $staffId, array $data): Staff
    {
        DB::beginTransaction();

        try {
            $this->staffRepository->update($staffId, $data);
            $staff = $this->staffRepository->findOrFail($staffId);

            // Update branches if provided
            if (isset($data['branches'])) {
                $staff->branches()->sync($data['branches']);
            }

            // Update services if provided
            if (isset($data['services'])) {
                $staff->services()->sync($data['services']);
            }

            DB::commit();

            return $staff->load(['branches', 'services']);

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete staff member
     */
    public function deleteStaff(int $staffId): bool
    {
        DB::beginTransaction();

        try {
            $staff = $this->staffRepository->findOrFail($staffId);

            // Detach all relationships
            $staff->branches()->detach();
            $staff->services()->detach();

            // Delete the staff member
            $deleted = $this->staffRepository->delete($staffId);

            DB::commit();

            return $deleted;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get staff members for branch
     */
    public function getStaffForBranch(int $branchId, array $filters = []): Collection
    {
        return $this->staffRepository->getByBranch($branchId, $filters);
    }

    /**
     * Get active staff members
     */
    public function getActiveStaff(int $branchId): Collection
    {
        return $this->staffRepository->getActive($branchId);
    }

    /**
     * Get staff by service
     */
    public function getStaffByService(int $serviceId): Collection
    {
        return $this->staffRepository->getByService($serviceId);
    }

    /**
     * Get staff performance metrics
     */
    public function getPerformanceMetrics(int $staffId, ?string $startDate = null, ?string $endDate = null): array
    {
        return $this->staffRepository->getPerformanceMetrics($staffId, $startDate, $endDate);
    }

    /**
     * Search staff members
     */
    public function searchStaff(string $query, int $branchId): Collection
    {
        return $this->staffRepository->search($query, $branchId);
    }

    /**
     * Activate staff member
     */
    public function activateStaff(int $staffId): Staff
    {
        $this->staffRepository->update($staffId, ['status' => 'active']);
        return $this->staffRepository->findOrFail($staffId);
    }

    /**
     * Deactivate staff member
     */
    public function deactivateStaff(int $staffId): Staff
    {
        $this->staffRepository->update($staffId, ['status' => 'inactive']);
        return $this->staffRepository->findOrFail($staffId);
    }

    /**
     * Assign service to staff
     */
    public function assignService(int $staffId, int $serviceId, ?string $proficiencyLevel = null): Staff
    {
        $staff = $this->staffRepository->findOrFail($staffId);

        $pivotData = [];
        if ($proficiencyLevel) {
            $pivotData['proficiency_level'] = $proficiencyLevel;
        }

        $staff->services()->attach($serviceId, $pivotData);

        return $staff->load('services');
    }

    /**
     * Remove service from staff
     */
    public function removeService(int $staffId, int $serviceId): Staff
    {
        $staff = $this->staffRepository->findOrFail($staffId);
        $staff->services()->detach($serviceId);

        return $staff->load('services');
    }

    /**
     * Assign branch to staff
     */
    public function assignBranch(int $staffId, int $branchId, bool $isPrimary = false): Staff
    {
        $staff = $this->staffRepository->findOrFail($staffId);

        $pivotData = ['is_primary_branch' => $isPrimary];
        $staff->branches()->attach($branchId, $pivotData);

        return $staff->load('branches');
    }

    /**
     * Remove branch from staff
     */
    public function removeBranch(int $staffId, int $branchId): Staff
    {
        $staff = $this->staffRepository->findOrFail($staffId);
        $staff->branches()->detach($branchId);

        return $staff->load('branches');
    }
}
