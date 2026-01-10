<?php

namespace App\Domain\Inventory\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ProductRepositoryInterface
{
    /**
     * Find a product by ID
     */
    public function find(int $id): ?Model;

    /**
     * Find a product by ID or fail
     */
    public function findOrFail(int $id): Model;

    /**
     * Find product by SKU
     */
    public function findBySku(string $sku);

    /**
     * Create a new product
     */
    public function create(array $data): Model;

    /**
     * Update a product
     */
    public function update(int $id, array $data): bool;

    /**
     * Delete a product
     */
    public function delete(int $id): bool;

    /**
     * Get all active products
     */
    public function getActive(): Collection;

    /**
     * Get published products
     */
    public function getPublished(): Collection;

    /**
     * Get featured products
     */
    public function getFeatured(): Collection;

    /**
     * Get products by category
     */
    public function getByCategory(int $categoryId): Collection;

    /**
     * Get products available in branch
     */
    public function getAvailableInBranch(int $branchId): Collection;

    /**
     * Get low stock products for branch
     */
    public function getLowStockForBranch(int $branchId): Collection;

    /**
     * Get paginated products
     */
    public function getPaginated(int $perPage = 15, array $filters = []): LengthAwarePaginator;

    /**
     * Search products
     */
    public function search(string $query): Collection;
}
