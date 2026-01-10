<?php

namespace App\Domain\Inventory\Repositories;

use App\Domain\Shared\Repositories\BaseRepository;
use App\Models\BranchProductInventory;
use Illuminate\Database\Eloquent\Collection;

class InventoryRepository extends BaseRepository implements InventoryRepositoryInterface
{
    /**
     * Resolve the BranchProductInventory model
     */
    protected function resolveModel(): BranchProductInventory
    {
        return new BranchProductInventory();
    }

    /**
     * Get inventory for product in branch
     */
    public function getForProductInBranch(int $productId, int $branchId)
    {
        return $this->query()
            ->where('product_id', $productId)
            ->where('branch_id', $branchId)
            ->first();
    }

    /**
     * Get all inventory for branch
     */
    public function getForBranch(int $branchId): Collection
    {
        return $this->query()
            ->where('branch_id', $branchId)
            ->with(['product', 'productVariant'])
            ->orderBy('product_id')
            ->get();
    }

    /**
     * Get low stock items for branch
     */
    public function getLowStockForBranch(int $branchId): Collection
    {
        return $this->query()
            ->where('branch_id', $branchId)
            ->lowStock()
            ->with(['product', 'productVariant'])
            ->orderBy('quantity_on_hand')
            ->get();
    }

    /**
     * Get available inventory for branch
     */
    public function getAvailableForBranch(int $branchId): Collection
    {
        return $this->query()
            ->where('branch_id', $branchId)
            ->available()
            ->with(['product', 'productVariant'])
            ->orderBy('product_id')
            ->get();
    }

    /**
     * Reserve quantity
     */
    public function reserveQuantity(int $inventoryId, int $quantity): bool
    {
        $inventory = $this->findOrFail($inventoryId);
        return $inventory->reserveQuantity($quantity);
    }

    /**
     * Release reserved quantity
     */
    public function releaseQuantity(int $inventoryId, int $quantity): bool
    {
        $inventory = $this->findOrFail($inventoryId);
        $inventory->releaseQuantity($quantity);
        return true;
    }

    /**
     * Adjust stock level
     */
    public function adjustStock(int $inventoryId, int $quantity, string $type = 'adjustment'): bool
    {
        $inventory = $this->findOrFail($inventoryId);
        $inventory->adjustStock($quantity, $type);
        return true;
    }

    /**
     * Check if can fulfill quantity
     */
    public function canFulfillQuantity(int $inventoryId, int $quantity): bool
    {
        $inventory = $this->findOrFail($inventoryId);
        return $inventory->canFulfillQuantity($quantity);
    }
}
