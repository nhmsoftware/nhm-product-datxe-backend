<?php

declare(strict_types=1);

namespace App\Modules\Food\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Food\Interfaces\FoodOrderRepositoryInterface;
use App\Modules\Food\Model\FoodOrder;
use Illuminate\Support\Facades\DB;

final class FoodOrderRepository extends BaseRepository implements FoodOrderRepositoryInterface
{
    public function getModel(): string
    {
        return FoodOrder::class;
    }

    public function createOrder(array $orderData, array $itemsData): FoodOrder
    {
        return DB::transaction(function () use ($orderData, $itemsData) {
            /** @var FoodOrder $order */
            $order = $this->create($orderData);

            foreach ($itemsData as $itemData) {
                $options = $itemData['options'] ?? [];
                unset($itemData['options']);

                $item = $order->items()->create($itemData);

                foreach ($options as $option) {
                    $item->options()->create($option);
                }
            }

            return $order->load('items.options');
        });
    }

    public function getDetail(string $orderId, ?string $merchantId = null): ?array
    {
        $query = $this->getQuery()
            ->select('food_orders.*', 'customer_profiles.full_name as customer_name')
            ->leftJoin('customer_profiles', 'food_orders.customer_id', '=', 'customer_profiles.user_id')
            ->where('food_orders.id', $orderId)
            ->with('items.options');

        if ($merchantId) {
            $query->where('food_orders.merchant_id', $merchantId);
        }

        $order = $query->first();

        return $order ? $order->toArray() : null;
    }

    public function countOrdersByMerchant(string $merchantId, string $period = 'today'): int
    {
        $query = $this->getQuery()
            ->where('merchant_id', $merchantId)
            ->whereNull('deleted_at');

        match ($period) {
            'week'  => $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]),
            'month' => $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year),
            default => $query->whereDate('created_at', now()->toDateString()),
        };

        return $query->count();
    }

    public function sumRevenueByMerchant(string $merchantId, string $period = 'today'): float
    {
        $query = $this->getQuery()
            ->where('merchant_id', $merchantId)
            ->where('status', 6) // FoodOrderStatus::DELIVERED
            ->whereNull('deleted_at');

        match ($period) {
            'week'  => $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]),
            'month' => $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year),
            default => $query->whereDate('created_at', now()->toDateString()),
        };

        return (float) $query->sum('total_price');
    }

    public function countCompletedOrdersByMerchant(string $merchantId, string $period = 'today'): int
    {
        $query = $this->getQuery()
            ->where('merchant_id', $merchantId)
            ->where('status', 6) // FoodOrderStatus::DELIVERED
            ->whereNull('deleted_at');

        match ($period) {
            'week'  => $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]),
            'month' => $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year),
            default => $query->whereDate('created_at', now()->toDateString()),
        };

        return $query->count();
    }

    public function getRevenueChartData(string $merchantId, string $period = 'today'): array
    {
        $trunc = match ($period) {
            'today' => 'hour',
            default => 'day',
        };

        $query = $this->getQuery()
            ->select([
                DB::raw("date_trunc('$trunc', created_at) as time_label"),
                DB::raw("SUM(total_price) as revenue")
            ])
            ->where('merchant_id', $merchantId)
            ->where('status', 6) // FoodOrderStatus::DELIVERED
            ->whereNull('deleted_at');

        match ($period) {
            'week'  => $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]),
            'month' => $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year),
            default => $query->whereDate('created_at', now()->toDateString()),
        };

        return $query->groupBy('time_label')
            ->orderBy('time_label')
            ->get()
            ->map(fn($item) => [
                'label'   => $item->time_label,
                'revenue' => (float) $item->revenue
            ])
            ->toArray();
    }

    public function updateFoodOrderStatus(string $orderId, int $status): bool
    {
        return (bool) $this->getQuery()
            ->where('id', $orderId)
            ->update([
                'status'     => $status,
                'updated_at' => now(),
            ]);
    }

    public function resetCancellationRequest(string $orderId): bool
    {
        return (bool) $this->getQuery()
            ->where('id', $orderId)
            ->update([
                'is_cancel_requested' => false,
                'updated_at' => now(),
            ]);
    }

    /**
     * @inheritDoc
     */
    public function listAllFoodOrdersForAdmin(): \Illuminate\Support\Collection
    {
        return $this->getQuery()
            ->with(['customer.customerProfile', 'merchant', 'ride.driver.driverProfile'])
            ->latest()
            ->get();
    }

    /**
     * @inheritDoc
     */
    public function getMerchantOrders(string $merchantId, ?array $statuses = null, int $perPage = 20, int $page = 1): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = $this->getQuery()
            ->with(['items.options', 'customer.customerProfile', 'ride.driver.driverProfile'])
            ->where('merchant_id', $merchantId);

        if ($statuses !== null) {
            $query->whereIn('status', $statuses);
        }

        return $query->latest()->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * @inheritDoc
     */
    public function updateFoodOrderStatusByRideId(string $rideId, int $status): bool
    {
        return (bool) $this->getQuery()
            ->where('ride_id', $rideId)
            ->update([
                'status'     => $status,
                'updated_at' => now(),
            ]);
    }
}

