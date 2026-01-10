<?php

namespace App\Domain\Inventory\Services;

use App\Models\ProductStockMovement;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Exception;

class StockMovementService
{
    /**
     * Record a stock movement
     */
    public function recordMovement(array $data): ProductStockMovement
    {
        return ProductStockMovement::create($data);
    }

    /**
     * Get stock movements for branch
     */
    public function getBranchMovements(int $branchId, ?string $startDate = null, ?string $endDate = null): Collection
    {
        $query = ProductStockMovement::where('branch_id', $branchId)
            ->with(['product', 'productVariant', 'staff']);

        if ($startDate && $endDate) {
            $query->forDateRange($startDate, $endDate);
        } elseif ($startDate) {
            $query->where('movement_date', '>=', $startDate);
        }

        return $query->orderBy('movement_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get stock movements for product
     */
    public function getProductMovements(int $productId, ?int $branchId = null): Collection
    {
        $query = ProductStockMovement::where('product_id', $productId)
            ->with(['branch', 'productVariant', 'staff']);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->orderBy('movement_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get movements by type
     */
    public function getMovementsByType(int $branchId, string $type, ?string $startDate = null, ?string $endDate = null): Collection
    {
        $query = ProductStockMovement::where('branch_id', $branchId)
            ->where('movement_type', $type)
            ->with(['product', 'productVariant']);

        if ($startDate && $endDate) {
            $query->forDateRange($startDate, $endDate);
        }

        return $query->orderBy('movement_date', 'desc')->get();
    }

    /**
     * Get incoming movements
     */
    public function getIncomingMovements(int $branchId, ?string $startDate = null, ?string $endDate = null): Collection
    {
        $query = ProductStockMovement::where('branch_id', $branchId)
            ->incoming()
            ->with(['product', 'productVariant']);

        if ($startDate && $endDate) {
            $query->forDateRange($startDate, $endDate);
        }

        return $query->orderBy('movement_date', 'desc')->get();
    }

    /**
     * Get outgoing movements
     */
    public function getOutgoingMovements(int $branchId, ?string $startDate = null, ?string $endDate = null): Collection
    {
        $query = ProductStockMovement::where('branch_id', $branchId)
            ->outgoing()
            ->with(['product', 'productVariant']);

        if ($startDate && $endDate) {
            $query->forDateRange($startDate, $endDate);
        }

        return $query->orderBy('movement_date', 'desc')->get();
    }

    /**
     * Get stock movement statistics
     */
    public function getMovementStatistics(int $branchId, string $startDate, string $endDate): array
    {
        $movements = ProductStockMovement::where('branch_id', $branchId)
            ->forDateRange($startDate, $endDate)
            ->get();

        $incoming = $movements->filter(fn($m) => $m->isIncoming());
        $outgoing = $movements->filter(fn($m) => $m->isOutgoing());

        $stats = [
            'total_movements' => $movements->count(),
            'incoming_movements' => $incoming->count(),
            'outgoing_movements' => $outgoing->count(),
            'incoming_quantity' => $incoming->sum('quantity'),
            'outgoing_quantity' => abs($outgoing->sum('quantity')),
            'incoming_value' => $incoming->sum('total_value'),
            'outgoing_value' => $outgoing->sum('total_value'),
            'by_type' => [],
        ];

        // Group by movement type
        $byType = $movements->groupBy('movement_type');
        foreach ($byType as $type => $typeMovements) {
            $stats['by_type'][$type] = [
                'count' => $typeMovements->count(),
                'quantity' => $typeMovements->sum('quantity'),
                'value' => $typeMovements->sum('total_value'),
            ];
        }

        return $stats;
    }

    /**
     * Get stock movement summary for product
     */
    public function getProductMovementSummary(int $productId, int $branchId, string $startDate, string $endDate): array
    {
        $movements = ProductStockMovement::where('product_id', $productId)
            ->where('branch_id', $branchId)
            ->forDateRange($startDate, $endDate)
            ->get();

        $incoming = $movements->filter(fn($m) => $m->isIncoming())->sum('quantity');
        $outgoing = abs($movements->filter(fn($m) => $m->isOutgoing())->sum('quantity'));

        return [
            'product_id' => $productId,
            'branch_id' => $branchId,
            'period_start' => $startDate,
            'period_end' => $endDate,
            'total_movements' => $movements->count(),
            'incoming_quantity' => $incoming,
            'outgoing_quantity' => $outgoing,
            'net_change' => $incoming - $outgoing,
            'movements' => $movements,
        ];
    }

    /**
     * Get recent movements
     */
    public function getRecentMovements(int $branchId, int $limit = 10): Collection
    {
        return ProductStockMovement::where('branch_id', $branchId)
            ->with(['product', 'productVariant', 'staff'])
            ->orderBy('movement_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
