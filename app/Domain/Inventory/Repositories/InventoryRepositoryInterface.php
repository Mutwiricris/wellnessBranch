<?php

namespace App\Domain\Inventory\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface InventoryRepositoryInterface
{
    /**
     * Find inventory by ID
     */
    public function find(int $id): ?Model;

    /**
     * Find inventory by ID or fail
     */
    public function findOrFail(int $id): Model;

    /**
     * Create inventory record
     */
    public function create(array $data): Model;

    /**
     * Update inventory record
     */
    public function update(int $id, array $data): bool;

    /**
     * Delete inventory record
     */
    public function delete(int $id): bool;

    /**
     * Get inventory for product in branch
     */
    public function getForProductInBranch(int $productId, int $branchId);

    /**
     * Get all inventory for branch
     */
    public function getForBranch(int $branchId): Collection;

    /**
     * Get low stock items for branch
     */
    public function getLowStockForBranch(int $branchId): Collection;

    /**
     * Get available inventory for branch
     */
    public function getAvailableForBranch(int $branchId): Collection;

    /**
     * Reserve quantity
     */
    public function reserveQuantity(int $inventoryId, int $quantity): bool;

    /**
     * Release reserved quantity
     */
    public function releaseQuantity(int $inventoryId, int $quantity): bool;

    /**
     * Adjust stock level
     */
    public function adjustStock(int $inventoryId, int $quantity, string $type = 'adjustment'): bool;

    /**
     * Check if can fulfill quantity
     */
    public function canFulfillQuantity(int $inventoryId, int $quantity): bool;
}
