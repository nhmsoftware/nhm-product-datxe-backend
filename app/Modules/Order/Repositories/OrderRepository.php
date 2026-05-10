<?php

declare(strict_types=1);

namespace App\Modules\Order\Repositories;

use App\Modules\Order\DTO\GetOrderHistoryFilterDTO;
use App\Modules\Order\Interfaces\OrderRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class OrderRepository implements OrderRepositoryInterface
{
    public function getHistory(GetOrderHistoryFilterDTO $filters): LengthAwarePaginator
    {
        $rideQuery = DB::table('rides')
            ->select([
                'id',
                DB::raw("'ride' as service_type"),
                'total_fare as total_price',
                'status',
                'pickup_address',
                'destination_address',
                'created_at'
            ])
            ->where('customer_id', $filters->customerId);

        $foodQuery = DB::table('food_orders')
            ->select([
                'id',
                DB::raw("'food' as service_type"),
                'total_price',
                'status',
                DB::raw('NULL as pickup_address'),
                'delivery_address as destination_address',
                'created_at'
            ])
            ->where('customer_id', $filters->customerId);

        // Apply filters
        if ($filters->serviceType === 'ride') {
            $query = $rideQuery;
        } elseif ($filters->serviceType === 'food') {
            $query = $foodQuery;
        } else {
            $query = $rideQuery->unionAll($foodQuery);
        }

        // Sort by created_at desc
        $orderedQuery = DB::table(DB::raw("({$query->toSql()}) as combined_orders"))
            ->mergeBindings($query)
            ->orderByDesc('created_at');

        return $orderedQuery->paginate($filters->perPage);
    }

    public function getDetail(string $orderId, string $serviceType, ?string $merchantId = null): ?array
    {
        if ($serviceType === 'ride') {
            $query = DB::table('rides')
                ->select('rides.*', 'customer_profiles.full_name as customer_name')
                ->leftJoin('customer_profiles', 'rides.customer_id', '=', 'customer_profiles.user_id')
                ->where('rides.id', $orderId);

            if ($merchantId) {
                // Rides might not have merchant_id directly in the same way food_orders do, 
                // but if we need to filter for a specific merchant who owns the service, we add it here.
                // For now, let's assume merchantId filter is mainly for food orders.
            }

            $ride = $query->first();
            return $ride ? (array) $ride : null;
        }

        if ($serviceType === 'food') {
            $query = DB::table('food_orders')
                ->select('food_orders.*', 'customer_profiles.full_name as customer_name')
                ->leftJoin('customer_profiles', 'food_orders.customer_id', '=', 'customer_profiles.user_id')
                ->where('food_orders.id', $orderId);

            if ($merchantId) {
                $query->where('food_orders.merchant_id', $merchantId);
            }

            $order = $query->first();
            
            if ($order) {
                $order = (array) $order;
                $order['items'] = DB::table('food_order_items')
                    ->where('food_order_id', $orderId)
                    ->get()
                    ->map(function ($item) {
                        $item = (array) $item;
                        $item['options'] = DB::table('food_order_item_options')
                            ->where('food_order_item_id', $item['id'])
                            ->get()
                            ->toArray();
                        return $item;
                    })
                    ->toArray();
                return $order;
            }
        }

        return null;
    }

    public function countDailyOrdersByMerchant(string $merchantId): int
    {
        return DB::table('food_orders')
            ->where('merchant_id', $merchantId)
            ->whereDate('created_at', now()->toDateString())
            ->whereNull('deleted_at')
            ->count();
    }

    public function sumDailyRevenueByMerchant(string $merchantId): float
    {
        return (float) DB::table('food_orders')
            ->where('merchant_id', $merchantId)
            ->where('status', 6) // FoodOrderStatus::DELIVERED
            ->whereDate('created_at', now()->toDateString())
            ->whereNull('deleted_at')
            ->sum('total_price');
    }

    public function updateFoodOrderStatus(string $orderId, int $status): bool
    {
        return (bool) DB::table('food_orders')
            ->where('id', $orderId)
            ->update([
                'status'     => $status,
                'updated_at' => now(),
            ]);
    }

    public function resetCancellationRequest(string $orderId): bool
    {
        return (bool) DB::table('food_orders')
            ->where('id', $orderId)
            ->update([
                'is_cancel_requested' => false,
                'updated_at' => now(),
            ]);
    }
}
