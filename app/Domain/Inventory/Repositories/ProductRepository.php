<?php

namespace App\Domain\Inventory\Repositories;

use App\Domain\Shared\Repositories\BaseRepository;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductRepository extends BaseRepository implements ProductRepositoryInterface
{
    /**
     * Resolve the Product model
     */
    protected function resolveModel(): Product
    {
        return new Product();
    }

    /**
     * Find product by SKU
     */
    public function findBySku(string $sku)
    {
        return $this->model->newQuery()
            ->where('sku', $sku)
            ->first();
    }

    /**
     * Get all active products
     */
    public function getActive(): Collection
    {
        return $this->model->newQuery()
            ->active()
            ->with(['categories', 'branchInventory'])
            ->orderBy('name')
            ->get();
    }

    /**
     * Get published products
     */
    public function getPublished(): Collection
    {
        return $this->model->newQuery()
            ->published()
            ->with(['categories', 'variants'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get featured products
     */
    public function getFeatured(): Collection
    {
        return $this->model->newQuery()
            ->featured()
            ->published()
            ->with(['categories', 'variants'])
            ->orderBy('sort_order')
            ->limit(10)
            ->get();
    }

    /**
     * Get products by category
     */
    public function getByCategory(int $categoryId): Collection
    {
        return $this->model->newQuery()
            ->whereHas('categories', function ($q) use ($categoryId) {
                $q->where('product_categories.id', $categoryId);
            })
            ->active()
            ->with(['categories', 'variants'])
            ->orderBy('name')
            ->get();
    }

    /**
     * Get products available in branch
     */
    public function getAvailableInBranch(int $branchId): Collection
    {
        return $this->model->newQuery()
            ->whereHas('branchInventory', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                    ->where('is_available', true)
                    ->where('quantity_on_hand', '>', 0);
            })
            ->with(['branchInventory' => function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            }])
            ->active()
            ->orderBy('name')
            ->get();
    }

    /**
     * Get low stock products for branch
     */
    public function getLowStockForBranch(int $branchId): Collection
    {
        return $this->model->newQuery()
            ->whereHas('branchInventory', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                    ->whereRaw('quantity_on_hand <= reorder_level');
            })
            ->with(['branchInventory' => function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            }])
            ->orderBy('name')
            ->get();
    }

    /**
     * Get paginated products
     */
    public function getPaginated(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['category_id'])) {
            $query->whereHas('categories', function ($q) use ($filters) {
                $q->where('product_categories.id', $filters['category_id']);
            });
        }

        if (isset($filters['is_featured'])) {
            $query->where('is_featured', $filters['is_featured']);
        }

        if (isset($filters['track_inventory'])) {
            $query->where('track_inventory', $filters['track_inventory']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                    ->orWhere('sku', 'like', "%{$filters['search']}%")
                    ->orWhere('description', 'like', "%{$filters['search']}%");
            });
        }

        return $query->with(['categories', 'branchInventory'])
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * Search products
     */
    public function search(string $query): Collection
    {
        return $this->model->newQuery()
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('sku', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%");
            })
            ->active()
            ->with(['categories', 'branchInventory'])
            ->limit(20)
            ->get();
    }
}
