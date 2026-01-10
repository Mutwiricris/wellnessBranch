<?php

namespace App\Domain\Shared\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;

abstract class BaseRepository
{
    protected Model $model;
    protected ?int $currentBranchId = null;

    public function __construct()
    {
        $this->model = $this->resolveModel();
        $this->currentBranchId = $this->getCurrentBranchId();
    }

    /**
     * Resolve the model instance for this repository
     */
    abstract protected function resolveModel(): Model;

    /**
     * Get current branch ID from Filament tenant context
     */
    protected function getCurrentBranchId(): ?int
    {
        $tenant = \Filament\Facades\Filament::getTenant();
        return $tenant?->id;
    }

    /**
     * Get base query with automatic branch scoping
     */
    protected function query(): Builder
    {
        $query = $this->model->newQuery();

        // Apply branch scoping if model has branch_id and we have a current branch
        if ($this->currentBranchId && $this->hasBranchColumn()) {
            $query->where('branch_id', $this->currentBranchId);
        }

        return $query;
    }

    /**
     * Check if model has branch_id column
     */
    protected function hasBranchColumn(): bool
    {
        return in_array('branch_id', $this->model->getFillable());
    }

    /**
     * Find a record by ID
     */
    public function find(int $id): ?Model
    {
        return $this->query()->find($id);
    }

    /**
     * Find a record by ID or fail
     */
    public function findOrFail(int $id): Model
    {
        return $this->query()->findOrFail($id);
    }

    /**
     * Get all records
     */
    public function all(): Collection
    {
        return $this->query()->get();
    }

    /**
     * Create a new record
     */
    public function create(array $data): Model
    {
        // Automatically add branch_id if applicable
        if ($this->currentBranchId && !isset($data['branch_id']) && $this->hasBranchColumn()) {
            $data['branch_id'] = $this->currentBranchId;
        }

        return $this->model->create($data);
    }

    /**
     * Update a record
     */
    public function update(int $id, array $data): bool
    {
        $record = $this->findOrFail($id);
        return $record->update($data);
    }

    /**
     * Delete a record
     */
    public function delete(int $id): bool
    {
        $record = $this->findOrFail($id);
        return $record->delete();
    }

    /**
     * Count all records
     */
    public function count(): int
    {
        return $this->query()->count();
    }

    /**
     * Get paginated results
     */
    public function paginate(int $perPage = 15)
    {
        return $this->query()->paginate($perPage);
    }

    /**
     * Find records where column matches value
     */
    public function findWhere(string $column, mixed $value): Collection
    {
        return $this->query()->where($column, $value)->get();
    }

    /**
     * Find first record where column matches value
     */
    public function findWhereFirst(string $column, mixed $value): ?Model
    {
        return $this->query()->where($column, $value)->first();
    }
}
