<?php

namespace App\Domain\Inventory\Services;

use App\Domain\Inventory\Repositories\InventoryRepositoryInterface;
use App\Domain\Inventory\Repositories\ProductRepositoryInterface;
use App\Models\BranchProductInventory;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Exception;

class InventoryService
{
    public function __construct(
        private InventoryRepositoryInterface $inventoryRepository,
        private ProductRepositoryInterface $productRepository
    ) {}

    /**
     * Get inventory for branch
     */
    public function getBranchInventory(int $branchId, array $filters = []): Collection
    {
        $inventory = $this->inventoryRepository->getForBranch($branchId);

        // Apply filters
        if (isset($filters['low_stock']) && $filters['low_stock']) {
            $inventory = $inventory->filter(fn($item) => $item->isLowStock());
        }

        if (isset($filters['available_only']) && $filters['available_only']) {
            $inventory = $inventory->filter(fn($item) => $item->is_available && $item->quantity_available > 0);
        }

        return $inventory;
    }

    /**
     * Get low stock items
     */
    public function getLowStockItems(int $branchId): Collection
    {
        return $this->inventoryRepository->getLowStockForBranch($branchId);
    }

    /**
     * Get product inventory for branch
     */
    public function getProductInventory(int $productId, int $branchId): ?BranchProductInventory
    {
        return $this->inventoryRepository->getForProductInBranch($productId, $branchId);
    }

    /**
     * Create or update inventory for product in branch
     */
    public function setProductInventory(int $productId, int $branchId, array $data): BranchProductInventory
    {
        $inventory = $this->inventoryRepository->getForProductInBranch($productId, $branchId);

        if ($inventory) {
            $this->inventoryRepository->update($inventory->id, $data);
            return $this->inventoryRepository->findOrFail($inventory->id);
        }

        $data['product_id'] = $productId;
        $data['branch_id'] = $branchId;

        return $this->inventoryRepository->create($data);
    }

    /**
     * Adjust stock level
     */
    public function adjustStock(int $productId, int $branchId, int $quantity, string $type = 'adjustment', ?string $notes = null): bool
    {
        DB::beginTransaction();

        try {
            $inventory = $this->inventoryRepository->getForProductInBranch($productId, $branchId);

            if (!$inventory) {
                throw new Exception('Inventory record not found for this product in this branch');
            }

            $this->inventoryRepository->adjustStock($inventory->id, $quantity, $type);

            DB::commit();
            return true;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Reserve inventory for order/booking
     */
    public function reserveInventory(int $productId, int $branchId, int $quantity): bool
    {
        $inventory = $this->inventoryRepository->getForProductInBranch($productId, $branchId);

        if (!$inventory) {
            throw new Exception('Product not available in this branch');
        }

        if (!$inventory->canFulfillQuantity($quantity)) {
            throw new Exception('Insufficient inventory to fulfill this request');
        }

        return $this->inventoryRepository->reserveQuantity($inventory->id, $quantity);
    }

    /**
     * Release reserved inventory
     */
    public function releaseReservation(int $productId, int $branchId, int $quantity): bool
    {
        $inventory = $this->inventoryRepository->getForProductInBranch($productId, $branchId);

        if (!$inventory) {
            throw new Exception('Inventory record not found');
        }

        return $this->inventoryRepository->releaseQuantity($inventory->id, $quantity);
    }

    /**
     * Fulfill order (reduce actual stock)
     */
    public function fulfillOrder(int $productId, int $branchId, int $quantity): bool
    {
        DB::beginTransaction();

        try {
            $inventory = $this->inventoryRepository->getForProductInBranch($productId, $branchId);

            if (!$inventory) {
                throw new Exception('Inventory record not found');
            }

            // Release reservation and reduce stock
            $this->inventoryRepository->releaseQuantity($inventory->id, $quantity);
            $this->inventoryRepository->adjustStock($inventory->id, -$quantity, 'sale');

            DB::commit();
            return true;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Restock product
     */
    public function restockProduct(int $productId, int $branchId, int $quantity, ?float $unitCost = null): bool
    {
        DB::beginTransaction();

        try {
            $inventory = $this->inventoryRepository->getForProductInBranch($productId, $branchId);

            if (!$inventory) {
                // Create inventory record if doesn't exist
                $inventory = $this->inventoryRepository->create([
                    'product_id' => $productId,
                    'branch_id' => $branchId,
                    'quantity_on_hand' => $quantity,
                    'quantity_reserved' => 0,
                    'is_available' => true,
                    'last_restocked_at' => now(),
                ]);
            } else {
                $this->inventoryRepository->adjustStock($inventory->id, $quantity, 'in');
                $this->inventoryRepository->update($inventory->id, [
                    'last_restocked_at' => now(),
                ]);
            }

            DB::commit();
            return true;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Mark product as unavailable in branch
     */
    public function markAsUnavailable(int $productId, int $branchId): bool
    {
        $inventory = $this->inventoryRepository->getForProductInBranch($productId, $branchId);

        if (!$inventory) {
            throw new Exception('Inventory record not found');
        }

        return $this->inventoryRepository->update($inventory->id, [
            'is_available' => false,
        ]);
    }

    /**
     * Mark product as available in branch
     */
    public function markAsAvailable(int $productId, int $branchId): bool
    {
        $inventory = $this->inventoryRepository->getForProductInBranch($productId, $branchId);

        if (!$inventory) {
            throw new Exception('Inventory record not found');
        }

        return $this->inventoryRepository->update($inventory->id, [
            'is_available' => true,
        ]);
    }

    /**
     * Get inventory statistics for branch
     */
    public function getInventoryStatistics(int $branchId): array
    {
        $inventory = $this->inventoryRepository->getForBranch($branchId);

        $totalProducts = $inventory->count();
        $totalValue = $inventory->sum(function ($item) {
            return $item->quantity_on_hand * ($item->product->cost_price ?? 0);
        });

        $lowStockCount = $inventory->filter(fn($item) => $item->isLowStock())->count();
        $outOfStockCount = $inventory->filter(fn($item) => $item->quantity_on_hand == 0)->count();
        $totalReserved = $inventory->sum('quantity_reserved');

        return [
            'total_products' => $totalProducts,
            'total_value' => $totalValue,
            'low_stock_count' => $lowStockCount,
            'out_of_stock_count' => $outOfStockCount,
            'total_reserved' => $totalReserved,
        ];
    }

    /**
     * Check product availability
     */
    public function isProductAvailable(int $productId, int $branchId, int $quantity = 1): bool
    {
        $inventory = $this->inventoryRepository->getForProductInBranch($productId, $branchId);

        if (!$inventory || !$inventory->is_available) {
            return false;
        }

        return $inventory->canFulfillQuantity($quantity);
    }
}
