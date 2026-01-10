<?php

namespace App\Domain\POS\Services;

use App\Domain\Inventory\Repositories\ProductRepositoryInterface;
use App\Domain\Inventory\Services\InventoryService;
use App\Models\Service;
use Exception;

class CartService
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private InventoryService $inventoryService
    ) {}

    /**
     * Validate cart items
     */
    public function validateCartItems(array $items, int $branchId): array
    {
        $validatedItems = [];
        $errors = [];

        foreach ($items as $index => $item) {
            try {
                $validatedItem = $this->validateCartItem($item, $branchId);
                $validatedItems[] = $validatedItem;
            } catch (Exception $e) {
                $errors[] = "Item {$index}: " . $e->getMessage();
            }
        }

        if (!empty($errors)) {
            throw new Exception("Cart validation failed:\n" . implode("\n", $errors));
        }

        return $validatedItems;
    }

    /**
     * Validate a single cart item
     */
    public function validateCartItem(array $item, int $branchId): array
    {
        if (!isset($item['item_type']) || !in_array($item['item_type'], ['product', 'service'])) {
            throw new Exception('Invalid item type. Must be "product" or "service".');
        }

        if (!isset($item['quantity']) || $item['quantity'] <= 0) {
            throw new Exception('Quantity must be greater than 0');
        }

        if ($item['item_type'] === 'product') {
            return $this->validateProductItem($item, $branchId);
        } else {
            return $this->validateServiceItem($item, $branchId);
        }
    }

    /**
     * Validate product item
     */
    private function validateProductItem(array $item, int $branchId): array
    {
        if (!isset($item['product_id'])) {
            throw new Exception('Product ID is required for product items');
        }

        $product = $this->productRepository->find($item['product_id']);

        if (!$product) {
            throw new Exception('Product not found');
        }

        if ($product->status !== 'active') {
            throw new Exception("Product '{$product->name}' is not active");
        }

        // Check inventory availability
        $inventory = $this->inventoryService->getProductInventory($item['product_id'], $branchId);

        if (!$inventory) {
            throw new Exception("Product '{$product->name}' is not available in this branch");
        }

        if (!$inventory->canFulfillQuantity($item['quantity'])) {
            throw new Exception(
                "Insufficient stock for '{$product->name}'. Available: {$inventory->quantity_available}, Requested: {$item['quantity']}"
            );
        }

        // Get effective price
        $unitPrice = $item['unit_price'] ?? $inventory->effective_price;

        return [
            'item_type' => 'product',
            'item_id' => $product->id,
            'product_id' => $product->id,
            'product_variant_id' => $item['product_variant_id'] ?? null,
            'name' => $item['name'] ?? $product->name,
            'quantity' => $item['quantity'],
            'unit_price' => $unitPrice,
            'discount_amount' => $item['discount_amount'] ?? 0,
            'discount_type' => $item['discount_type'] ?? null,
            'tax_rate' => $item['tax_rate'] ?? 16, // Default 16% VAT
            'staff_id' => $item['staff_id'] ?? null,
            'notes' => $item['notes'] ?? null,
        ];
    }

    /**
     * Validate service item
     */
    private function validateServiceItem(array $item, int $branchId): array
    {
        if (!isset($item['service_id'])) {
            throw new Exception('Service ID is required for service items');
        }

        $service = Service::find($item['service_id']);

        if (!$service) {
            throw new Exception('Service not found');
        }

        if ($service->status !== 'active') {
            throw new Exception("Service '{$service->name}' is not active");
        }

        // Get effective price
        $unitPrice = $item['unit_price'] ?? $service->price;

        return [
            'item_type' => 'service',
            'item_id' => $service->id,
            'service_id' => $service->id,
            'name' => $item['name'] ?? $service->name,
            'quantity' => $item['quantity'],
            'unit_price' => $unitPrice,
            'discount_amount' => $item['discount_amount'] ?? 0,
            'discount_type' => $item['discount_type'] ?? null,
            'tax_rate' => $item['tax_rate'] ?? 16, // Default 16% VAT
            'staff_id' => $item['staff_id'] ?? null,
            'duration_minutes' => $item['duration_minutes'] ?? $service->duration_minutes,
            'notes' => $item['notes'] ?? null,
        ];
    }

    /**
     * Calculate cart totals
     */
    public function calculateTotals(array $items, array $options = []): array
    {
        $subtotal = 0;
        $totalDiscount = 0;
        $totalTax = 0;

        foreach ($items as $item) {
            $itemSubtotal = $item['unit_price'] * $item['quantity'];
            $itemDiscount = $item['discount_amount'] ?? 0;
            $itemTaxableAmount = $itemSubtotal - $itemDiscount;
            $itemTax = $itemTaxableAmount * ($item['tax_rate'] ?? 0) / 100;

            $subtotal += $itemSubtotal;
            $totalDiscount += $itemDiscount;
            $totalTax += $itemTax;
        }

        // Apply cart-level discount if provided
        $cartDiscount = $options['discount_amount'] ?? 0;
        $cartDiscountType = $options['discount_type'] ?? 'fixed';

        if ($cartDiscountType === 'percentage') {
            $cartDiscount = $subtotal * ($cartDiscount / 100);
        }

        $totalDiscount += $cartDiscount;

        // Calculate final total
        $total = $subtotal - $totalDiscount + $totalTax;

        return [
            'subtotal' => round($subtotal, 2),
            'discount' => round($totalDiscount, 2),
            'tax' => round($totalTax, 2),
            'total' => round($total, 2),
            'item_count' => count($items),
            'total_quantity' => array_sum(array_column($items, 'quantity')),
        ];
    }

    /**
     * Apply discount to cart
     */
    public function applyDiscount(array $totals, float $discountAmount, string $discountType = 'fixed'): array
    {
        if ($discountType === 'percentage') {
            $discountAmount = $totals['subtotal'] * ($discountAmount / 100);
        }

        $totalDiscount = $totals['discount'] + $discountAmount;
        $total = $totals['subtotal'] - $totalDiscount + $totals['tax'];

        return [
            'subtotal' => $totals['subtotal'],
            'discount' => round($totalDiscount, 2),
            'tax' => $totals['tax'],
            'total' => round($total, 2),
            'item_count' => $totals['item_count'],
            'total_quantity' => $totals['total_quantity'],
        ];
    }

    /**
     * Apply discount to specific item
     */
    public function applyItemDiscount(array $item, float $discountAmount, string $discountType = 'fixed'): array
    {
        if ($discountType === 'percentage') {
            $itemSubtotal = $item['unit_price'] * $item['quantity'];
            $discountAmount = $itemSubtotal * ($discountAmount / 100);
        }

        $item['discount_amount'] = $discountAmount;
        $item['discount_type'] = $discountType;

        return $item;
    }

    /**
     * Split payment calculation
     */
    public function calculatePaymentSplit(float $totalAmount, array $paymentMethods): array
    {
        $splits = [];
        $remaining = $totalAmount;

        foreach ($paymentMethods as $method => $amount) {
            if ($amount > 0) {
                $splits[] = [
                    'payment_method' => $method,
                    'amount' => min($amount, $remaining),
                ];
                $remaining -= $amount;

                if ($remaining <= 0) {
                    break;
                }
            }
        }

        if ($remaining > 0) {
            throw new Exception('Payment split does not cover the total amount');
        }

        return $splits;
    }

    /**
     * Get cart summary for display
     */
    public function getCartSummary(array $items, array $options = []): array
    {
        $validatedItems = $this->validateCartItems($items, $options['branch_id'] ?? 1);
        $totals = $this->calculateTotals($validatedItems, $options);

        return [
            'items' => $validatedItems,
            'totals' => $totals,
            'metadata' => [
                'customer_id' => $options['customer_id'] ?? null,
                'staff_id' => $options['staff_id'] ?? null,
                'discount_amount' => $options['discount_amount'] ?? 0,
                'discount_type' => $options['discount_type'] ?? 'fixed',
                'notes' => $options['notes'] ?? null,
            ],
        ];
    }

    /**
     * Check if all items are available
     */
    public function checkAvailability(array $items, int $branchId): array
    {
        $availability = [];

        foreach ($items as $item) {
            if ($item['item_type'] === 'product') {
                $inventory = $this->inventoryService->getProductInventory($item['product_id'], $branchId);

                $availability[] = [
                    'item_id' => $item['product_id'],
                    'item_type' => 'product',
                    'name' => $item['name'],
                    'requested_quantity' => $item['quantity'],
                    'available_quantity' => $inventory ? $inventory->quantity_available : 0,
                    'is_available' => $inventory && $inventory->canFulfillQuantity($item['quantity']),
                ];
            } else {
                // Services are always available (no inventory check)
                $availability[] = [
                    'item_id' => $item['service_id'],
                    'item_type' => 'service',
                    'name' => $item['name'],
                    'requested_quantity' => $item['quantity'],
                    'available_quantity' => null,
                    'is_available' => true,
                ];
            }
        }

        return $availability;
    }
}
