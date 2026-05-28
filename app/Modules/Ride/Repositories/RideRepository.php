<?php

declare(strict_types=1);

namespace App\Modules\Ride\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Ride\Interfaces\RideRepositoryInterface;
use App\Modules\Ride\Model\Enums\RideStatus;
use App\Modules\Ride\Model\Enums\RideTrackingStatus;
use App\Modules\Ride\Model\Enums\RideType;
use App\Modules\Ride\Model\Ride;
use App\Modules\Ride\Model\RideReject;
use Carbon\CarbonInterface;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class RideRepository extends BaseRepository implements RideRepositoryInterface
{
    public function getModel(): string
    {
        return Ride::class;
    }

    /**
     * Tìm ride draft theo ID và customer ID để xác thực quyền sở hữu.
     */
    public function findByIdAndCustomer(string $rideId, string $customerId): ?Ride
    {
        if (!is_numeric($rideId)) {
            return null;
        }

        /** @var Ride|null */
        return $this->model->where('id', $rideId)
            ->where('customer_id', $customerId)
            ->first();
    }

    public function findWithDriverDetail(string $rideId, string $customerId): ?Ride
    {
        if (!is_numeric($rideId)) {
            return null;
        }

        /** @var Ride|null */
        return $this->model->with(['driver', 'driver.driverProfile'])
            ->whereRaw('id = ?::bigint', [(string) $rideId])
            ->where(function ($query) use ($customerId) {
                $query->whereRaw('customer_id::text = ?', [(string) $customerId])
                      ->orWhereRaw('driver_id::text = ?', [(string) $customerId]);
            })
            ->first();
    }

    /**
     * Áp dụng voucher vào chuyến đi — lưu mã, discount và giá cuối (UC-11).
     */
    public function applyVoucher(string $rideId, string $voucherCode, float $discountAmount, float $finalPrice): bool
    {
        return (bool) $this->model->where('id', $rideId)->update([
            'voucher_code'    => $voucherCode,
            'discount_amount' => $discountAmount,
            'total_price'     => $finalPrice,
        ]);
    }

    /**
     * Xóa voucher khỏi chuyến đi, khôi phục giá gốc (UC-11 A4).
     */
    public function clearVoucher(string $rideId, float $originalPrice): bool
    {
        return (bool) $this->model->where('id', $rideId)->update([
            'voucher_code'    => null,
            'discount_amount' => 0,
            'total_price'     => $originalPrice,
        ]);
    }

    /**
     * Xác nhận đặt xe, chuyển trạng thái sang PENDING và chốt giá (UC-12).
     */
    public function confirmBooking(string $rideId, float $finalPrice): bool
    {
        return (bool) $this->model->where('id', $rideId)->update([
            'status'      => RideStatus::PENDING->value,
            'total_price' => $finalPrice,
        ]);
    }

    /**
     * Hủy chuyến đi, cập nhật lý do và phí hủy nếu có (UC-15).
     */
    public function cancel(string $rideId, ?string $reason, float $cancellationFee): bool
    {
        return (bool) $this->model->where('id', $rideId)->update([
            'status'           => RideStatus::CANCELLED->value,
            'cancel_reason'    => $reason,
            'cancellation_fee' => $cancellationFee,
        ]);
    }

    /**
     * Tính toán tổng chi tiêu của khách hàng trong một khoảng thời gian (UC-23).
     */
    public function getSpendingSummary(string $customerId, CarbonInterface $start, CarbonInterface $end): array
    {
        $data = $this->model
            ->where('customer_id', $customerId)
            ->where('status', RideStatus::COMPLETED->value)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('SUM(total_price) as total_amount, COUNT(*) as total_count')
            ->first();

        return [
            'total_amount' => (float) ($data->total_amount ?? 0),
            'total_count'  => (string) ($data->total_count ?? 0),
        ];
    }

    /**
     * Kiểm tra tài xế có chuyến đi nào đang diễn ra không (UC-31).
     */
    public function hasActiveRideByDriver(string $driverId): bool
    {
        return $this->model
            ->where('driver_id', $driverId)
            ->whereIn('status', [RideStatus::ACCEPTED->value, RideStatus::IN_PROGRESS->value])
            ->exists();
    }

    /**
     * Tài xế nhận chuyến đi — cập nhật status ACCEPTED và gán driver_id (UC-32).
     */
    public function acceptByDriver(string $rideId, string $driverId): bool
    {
        return (bool) $this->model->where('id', $rideId)->update([
            'status'          => RideStatus::ACCEPTED->value,
            'tracking_status' => RideTrackingStatus::DRIVER_ACCEPTED->value,
            'driver_id'       => $driverId,
        ]);
    }

    /**
     * Tài xế từ chối nhận đơn (UC-33 Reject).
     */
    public function rejectByDriver(string $rideId, string $driverId): bool
    {
        $ride = $this->model->find($rideId);
        if (!$ride) {
            return false;
        }

        return (bool) $ride->rejects()->updateOrCreate(
            ['driver_id' => $driverId],
            ['updated_at' => now(), 'created_at' => now()]
        );
    }

    /**
     * Tài xế hủy chuyến sau khi đã nhận (UC-33 Cancel).
     */
    public function cancelByDriver(string $rideId, string $reasonId): bool
    {
        return (bool) $this->model->where('id', $rideId)->update([
            'status'          => RideStatus::CANCELLED->value,
            'tracking_status' => RideTrackingStatus::DRIVER_CANCELLED->value,
            'cancel_reason'   => (string) $reasonId, // Lưu ID lý do hoặc map sang label
        ]);
    }
    /**
     * Tài xế xác nhận đã đón khách sau khi đã nhận (UC-36).
     */
    public function pickup(string $rideId): bool
    {
        return (bool) $this->model->where('id', $rideId)->update([
            'status' => RideStatus::PICKED_UP->value,
        ]);
    }

    /**
     * Tài xế bắt đầu thực hiện chuyến đi (UC-35 Start Trip).
     */
    public function startTrip(string $rideId): bool
    {
        return (bool) $this->model->where('id', $rideId)->update([
            'status'     => RideStatus::IN_PROGRESS->value,
            'started_at' => now(),
        ]);
    }

    /**
     * Tài xế hoàn thành chuyến đi (UC-40 Complete Trip).
     */
    public function completeTrip(string $rideId, float $finalFare, float $serviceFee, float $driverEarnings): bool
    {
        return (bool) $this->model->where('id', $rideId)->update([
            'status'          => RideStatus::COMPLETED->value,
            'completed_at'    => now(),
            'total_price'     => $finalFare,
            'service_fee'     => $serviceFee,
            'driver_earnings' => $driverEarnings,
        ]);
    }

    /**
     * Kiểm tra tài xế đã từng từ chối đơn này chưa.
     */
    public function isRejectedByDriver(string $rideId, string $driverId): bool
    {
        $ride = $this->model->find($rideId);
        if (!$ride) {
            return false;
        }

        return $ride->rejects()->where('driver_id', $driverId)->exists();
    }

    /**
     * @inheritDoc
     */
    public function updateStatus(string $rideId, RideStatus $status, ?string $reason = null): bool
    {
        $data = ['status' => $status->value];
        if ($reason !== null) {
            $data['cancel_reason'] = $reason;
        }

        return (bool) $this->model->where('id', $rideId)->update($data);
    }

    /**
     * @inheritDoc
     */
    public function findActiveByDriver(string $driverId): ?Ride
    {
        /** @var Ride|null */
        return $this->model
            ->where('driver_id', $driverId)
            ->whereIn('status', [
                RideStatus::ACCEPTED->value,
                RideStatus::PICKED_UP->value,
                RideStatus::IN_PROGRESS->value,
                RideStatus::CANCELLATION_REQUESTED->value
            ])
            ->latest()
            ->first();
    }

    /**
     * @inheritDoc
     */
    public function findActiveByCustomer(string $customerId): ?Ride
    {
        /** @var Ride|null */
        return $this->model
            ->where('customer_id', $customerId)
            ->whereIn('status', [
                RideStatus::PENDING->value,
                RideStatus::ACCEPTED->value,
                RideStatus::PICKED_UP->value,
                RideStatus::IN_PROGRESS->value,
                RideStatus::CANCELLATION_REQUESTED->value
            ])
            ->latest()
            ->first();
    }

    public function findTrackingRideByIdAndCustomer(string $rideId, string $customerId): ?Ride
    {
        if (!is_numeric($rideId)) {
            return null;
        }

        /** @var Ride|null */
        return $this->model->with(['driver.driverProfile'])
            ->where('id', $rideId)
            ->where('customer_id', $customerId)
            ->first();
    }

    public function assignDriver(string $rideId, string $driverId, CarbonInterface $acceptedAt): bool
    {
        return (bool) $this->model->where('id', $rideId)
            ->where('status', RideStatus::PENDING->value)
            ->whereNull('driver_id')
            ->update([
                'status'             => RideStatus::ACCEPTED->value,
                'tracking_status'    => RideTrackingStatus::DRIVER_ACCEPTED->value,
                'driver_id'          => $driverId,
                'driver_assigned_at' => $acceptedAt,
            ]);
    }

    public function findTrackingRideByIdAndDriver(string $rideId, string $driverId): ?Ride
    {
        /** @var Ride|null */
        return $this->model->with(['driver.driverProfile'])
            ->where('id', $rideId)
            ->where('driver_id', $driverId)
            ->first();
    }

    public function refreshTrackingHeartbeat(string $rideId, CarbonInterface $trackedAt): bool
    {
        return (bool) $this->model->where('id', $rideId)->update([
            'tracking_last_ping_at' => $trackedAt,
        ]);
    }

    public function markDriverArrived(string $rideId, CarbonInterface $arrivedAt): bool
    {
        return (bool) $this->model->where('id', $rideId)->update([
            'driver_arrived_at' => $arrivedAt,
            'tracking_status'   => RideTrackingStatus::DRIVER_ARRIVED->value,
        ]);
    }

    public function releaseDriverFromRide(string $rideId, ?string $reason): bool
    {
        return (bool) $this->model->where('id', $rideId)->update([
            'status'             => RideStatus::PENDING->value,
            'tracking_status'    => RideTrackingStatus::WAITING_DRIVER->value,
            'driver_id'          => null,
            'driver_assigned_at' => null,
            'driver_arrived_at'  => null,
            'cancel_reason'      => $reason,
        ]);
    }

    public function countCancellationsToday(string $driverId): int
    {
        return $this->model
            ->where('driver_id', $driverId)
            ->where('status', RideStatus::CANCELLED->value)
            ->where('updated_at', '>=', now()->startOfDay())
            ->count();
    }

    public function createIntercityRide(array $data): Ride
    {
        return $this->model->create($data);
    }

    public function createAirportRide(array $data): Ride
    {
        return $this->model->create($data);
    }

    public function findAvailableScheduledRides(int $vehicleType, array $filters): Collection
    {
        $query = $this->getQuery()
            ->where('status', RideStatus::PENDING->value)
            ->where('is_pushed_to_pool', true)
            ->where('vehicle_type', $vehicleType)
            // Lọc ra các đơn mà tài xế đã từ chối trước đó (nếu có lưu vết)
            ->whereNotExists(function ($q) use ($filters) {
                $q->select(DB::raw(1))
                    ->from('ride_rejects')
                    ->whereRaw('ride_rejects.ride_id = rides.id::text')
                    ->whereRaw('ride_rejects.driver_id::text = ?::text', [(string) ($filters['driverId'] ?? '')]);
            });

        \Illuminate\Support\Facades\Log::debug('Driver Scheduled Rides Filter:', [
            'vehicleType' => $vehicleType,
            'filters' => $filters
        ]);

        $startDate = $filters['travelDate'] ?? now()->toDateString();
        $query->where('travel_date', '>=', (string) $startDate);

        // Chỉ lọc travel_time nếu ngày đi bằng ngày bắt đầu lọc (thường là hôm nay)
        // Nếu ngày đi là ngày mai, thì 08:00 sáng vẫn phải hiển thị dù bây giờ là 10:00 sáng.
        if (!empty($filters['travelTime'])) {
            $query->where(function ($q) use ($filters, $startDate) {
                $q->where('travel_date', '>', (string) $startDate)
                    ->orWhere(function ($q2) use ($filters, $startDate) {
                        $q2->where('travel_date', (string) $startDate)
                            ->where('travel_time', '>=', (string) $filters['travelTime']);
                    });
            });
        }

        if (!empty($filters['rideType'])) {
            $query->where('ride_type', (int) $filters['rideType']);
        }

        if (isset($filters['minPrice'])) {
            $query->where('total_price', '>=', $filters['minPrice']);
        }

        if (isset($filters['maxPrice'])) {
            $query->where('total_price', '<=', $filters['maxPrice']);
        }

        if (!empty($filters['pickupArea'])) {
            $query->where('pickup_address', 'like', '%' . $filters['pickupArea'] . '%');
        }

        if (!empty($filters['destinationArea'])) {
            $query->where('destination_address', 'like', '%' . $filters['destinationArea'] . '%');
        }

        return $query->orderBy('travel_date')->orderBy('travel_time')->get();
    }

    public function findAvailableById(string $rideId): ?Ride
    {
        if (!is_numeric($rideId)) {
            return null;
        }

        /** @var Ride|null */
        return $this->model->whereRaw('id = ?::bigint', [$rideId])
            ->where('status', RideStatus::PENDING->value)
            ->first();
    }

    /**
     * Ghi đè findById để ép kiểu bigint an toàn trên PostgreSQL
     */
    public function findById(string|int $id, array $columns = ['*'], array $relations = []): ?Ride
    {
        if (!is_numeric($id)) {
            return null;
        }

        /** @var Ride|null */
        return $this->model->with($relations)
            ->select($columns)
            ->whereRaw('id = ?::bigint', [(string) $id])
            ->first();
    }

    public function findDriverAcceptedRides(string $driverId): Collection
    {
        return $this->model->with(['customer'])
            ->whereRaw('driver_id = ?::bigint', [(string) $driverId])
            ->whereNotIn('status', [
                RideStatus::COMPLETED->value,
                RideStatus::CANCELLED->value
            ])
            ->orderBy('travel_date')
            ->orderBy('travel_time')
            ->get();
    }

    /**
     * Đếm tổng số chuyến xe trong hệ thống.
     */
    public function countTotalOrders(): int
    {
        return $this->model->count();
    }

    /**
     * Tính tổng doanh thu hệ thống (các chuyến xe đã hoàn thành).
     */
    public function sumTotalRevenue(): float
    {
        return (float) $this->model
            ->where('status', RideStatus::COMPLETED->value)
            ->sum('total_price');
    }

    /**
     * @inheritDoc
     */
    public function sumTotalCommission(): float
    {
        return (float) $this->model
            ->where('status', RideStatus::COMPLETED->value)
            ->sum('service_fee');
    }

    /**
     * @inheritDoc
     */
    public function listScheduledRidesForAdmin(array $filters)
    {
        $query = $this->getQuery()
            ->with(['customer', 'driver'])
            ->whereNotIn('ride_type', [
                RideType::CHAUFFEUR->value,
                RideType::DELIVERY->value
            ]);

        if (!empty($filters['status'])) {
            $statusMap = [
                'waiting'   => RideStatus::PENDING->value,
                'assigned'  => RideStatus::ACCEPTED->value,
                'completed' => RideStatus::COMPLETED->value,
                'canceled'  => RideStatus::CANCELLED->value,
            ];

            $status = $statusMap[$filters['status']] ?? $filters['status'];
            $query->where('status', $status);
        }

        if (!empty($filters['keyword'])) {
            $keyword = '%' . $filters['keyword'] . '%';
            $query->where(function ($q) use ($keyword) {
                $q->whereRaw('id::text LIKE ?', [$keyword])
                    ->orWhere('pickup_address', 'like', $keyword)
                    ->orWhere('destination_address', 'like', $keyword)
                    ->orWhereHas('customer', function ($qc) use ($keyword) {
                        $qc->where('phone', 'like', $keyword);
                    });
            });
        }

        if (!empty($filters['no_pagination'])) {
            return $query->latest()->get();
        }

        return $query->latest()->paginate($filters['per_page'] ?? 15);
    }

    /**
     * @inheritDoc
     */
    public function listChauffeurRidesForAdmin(array $filters)
    {
        $query = $this->getQuery()
            ->with(['customer', 'driver'])
            ->where('ride_type', \App\Modules\Ride\Model\Enums\RideType::CHAUFFEUR->value);

        if (!empty($filters['status'])) {
            $statusMap = [
                'waiting'   => RideStatus::PENDING->value,
                'assigned'  => RideStatus::ACCEPTED->value,
                'completed' => RideStatus::COMPLETED->value,
                'canceled'  => RideStatus::CANCELLED->value,
            ];

            $status = $statusMap[$filters['status']] ?? $filters['status'];
            $query->where('status', $status);
        }

        if (!empty($filters['keyword'])) {
            $keyword = '%' . $filters['keyword'] . '%';
            $query->where(function ($q) use ($keyword) {
                $q->whereRaw('id::text LIKE ?', [$keyword])
                    ->orWhere('pickup_address', 'like', $keyword)
                    ->orWhere('destination_address', 'like', $keyword)
                    ->orWhereHas('customer', function ($qc) use ($keyword) {
                        $qc->where('phone', 'like', $keyword);
                    });
            });
        }

        return $query->latest()->paginate($filters['per_page'] ?? 15);
    }

    /**
     * @inheritDoc
     */
    public function pushToPool(array $rideIds): int
    {
        $rideIds = array_filter($rideIds, 'is_numeric');
        if (empty($rideIds)) {
            return 0;
        }

        return $this->model->whereIn('id', $rideIds)
            ->where('status', RideStatus::PENDING->value)
            ->update(['is_pushed_to_pool' => true]);
    }

    /**
     * Đẩy toàn bộ chuyến xe đặt trước đang chờ ra pool tài xế.
     * Được gọi khi Admin chuyển chế độ phân phối từ "Admin Priority" → "Open Pool".
     */
    public function pushAllPendingScheduledToPool(): int
    {
        return $this->model
            ->where('status', RideStatus::PENDING->value)
            ->where('is_pushed_to_pool', false)
            ->whereNotNull('travel_date')
            ->update(['is_pushed_to_pool' => true]);
    }

    /**
     * Ẩn toàn bộ chuyến xe đặt trước khỏi pool tài xế.
     * Được gọi khi Admin chuyển chế độ phân phối từ "Open Pool" → "Admin Priority".
     */
    public function hideAllPendingScheduledFromPool(): int
    {
        return $this->model
            ->where('status', RideStatus::PENDING->value)
            ->where('is_pushed_to_pool', true)
            ->whereNotNull('travel_date')
            ->update(['is_pushed_to_pool' => false]);
    }

    // =========================================================
    // UC-25: Giao hàng (Delivery)
    // =========================================================

    public function createDeliveryRide(array $data): \App\Modules\Ride\Model\Ride
    {
        /** @var \App\Modules\Ride\Model\Ride */
        return $this->model->create($data);
    }

    public function createDeliveryOrderDetail(array $data): \App\Modules\Ride\Model\DeliveryOrder
    {
        return \App\Modules\Ride\Model\DeliveryOrder::create($data);
    }

    // =========================================================
    // UC-37: Capture Pickup Proof
    // =========================================================

    /**
     * Lưu bằng chứng lấy hàng vào bảng rides.
     */
    public function savePickupProof(
        string $rideId,
        ?string $photoUrl,
        \Carbon\CarbonInterface $capturedAt,
        ?float $capturedLat,
        ?float $capturedLng,
        ?string $skipReason,
        ?string $note
    ): bool {
        return (bool) $this->model->where('id', $rideId)->update(array_filter([
            'pickup_proof_photo_url'   => $photoUrl,
            'pickup_proof_captured_at' => $capturedAt,
            'pickup_proof_skip_reason' => $skipReason,
            'pickup_proof_note'        => $note,
            'status'                   => RideStatus::PICKED_UP->value,
        ], fn ($v) => $v !== null));
    }

    /**
     * Lưu bằng chứng giao hàng (UC-38).
     */
    public function saveDeliveryProof(
        string $rideId,
        ?string $photoUrl,
        \Carbon\CarbonInterface $capturedAt,
        ?float $capturedLat,
        ?float $capturedLng,
        ?string $skipReason,
        ?string $note
    ): bool {
        return (bool) $this->model->where('id', $rideId)->update(array_filter([
            'delivery_proof_photo_url'   => $photoUrl,
            'delivery_proof_captured_at' => $capturedAt,
            'delivery_proof_skip_reason' => $skipReason,
            'delivery_proof_note'        => $note,
        ], fn ($v) => $v !== null));
    }

    /**
     * Tìm chuyến xe theo ID và Driver ID (UC-37 — xác thực quyền sở hữu).
     */
    public function findByIdAndDriver(string $rideId, string $driverId): ?Ride
    {
        if (!is_numeric($rideId)) {
            return null;
        }

        /** @var Ride|null */
        return $this->model->where('id', $rideId)
            ->where('driver_id', $driverId)
            ->first();
    }

    /**
     * @inheritDoc
     */
    public function getRevenueAnalytics(CarbonInterface $start, CarbonInterface $end, string $interval): array
    {
        $groupBy = match ($interval) {
            'month' => "TO_CHAR(created_at, 'YYYY-MM')",
            'year'  => "TO_CHAR(created_at, 'YYYY')",
            default => "TO_CHAR(created_at, 'YYYY-MM-DD')"
        };

        return $this->getQuery()
            ->whereBetween('created_at', [$start, $end])
            ->where('status', RideStatus::COMPLETED->value)
            ->select([
                DB::raw("$groupBy as period"),
                DB::raw("SUM(total_price) as gmv"),
                DB::raw("SUM(total_price - discount_amount) as actual_revenue"),
                DB::raw("COUNT(*) as order_count"),
                DB::raw("AVG(total_price) as aov")
            ])
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->toArray();
    }

    /**
     * @inheritDoc
     */
    public function getAreaAnalytics(CarbonInterface $start, CarbonInterface $end): array
    {
        // Phân tích theo khu vực (Dựa trên pickup_address, giả định phần cuối là Tỉnh/Thành)
        // Trong thực tế nên có bảng khu vực riêng, ở đây demo bằng cách lấy text sau dấu phẩy cuối cùng
        return $this->getQuery()
            ->whereBetween('created_at', [$start, $end])
            ->select([
                DB::raw("TRIM(SUBSTRING(pickup_address FROM '([^,]+)$')) as area"),
                DB::raw("COUNT(*) as total_rides"),
                DB::raw("SUM(total_price) as total_revenue")
            ])
            ->groupBy('area')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * @inheritDoc
     */
    public function getOrderOperationalStats(CarbonInterface $start, CarbonInterface $end): array
    {
        $total = $this->model->whereBetween('created_at', [$start, $end])->count();
        if ($total === 0) {
            return [
                'completion_rate' => 0,
                'cancellation_rate' => 0,
                'status_distribution' => [],
                'cancel_reasons' => []
            ];
        }

        $completed = $this->model->whereBetween('created_at', [$start, $end])
            ->where('status', RideStatus::COMPLETED->value)
            ->count();

        $cancelled = $this->model->whereBetween('created_at', [$start, $end])
            ->where('status', RideStatus::CANCELLED->value)
            ->count();

        $statusDistribution = $this->model->whereBetween('created_at', [$start, $end])
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->status instanceof \UnitEnum ? $item->status->value : $item->status => $item->count];
            })
            ->toArray();

        $cancelReasons = $this->model->whereBetween('created_at', [$start, $end])
            ->where('status', RideStatus::CANCELLED->value)
            ->whereNotNull('cancel_reason')
            ->select('cancel_reason', DB::raw('count(*) as count'))
            ->groupBy('cancel_reason')
            ->orderByDesc('count')
            ->get()
            ->toArray();

        return [
            'total_orders' => $total,
            'completed_orders' => $completed,
            'cancelled_orders' => $cancelled,
            'completion_rate' => round(($completed / $total) * 100, 2),
            'cancellation_rate' => round(($cancelled / $total) * 100, 2),
            'status_distribution' => $statusDistribution,
            'cancel_reasons' => $cancelReasons
        ];
    }

    /**
     * @inheritDoc
     */
    public function getCommissionAnalytics(CarbonInterface $start, CarbonInterface $end): array
    {
        // Thống kê hoa hồng bóc tách theo loại đội xe
        // driver_group_type: 1 = Xe nhà, 2 = Xe khách (Giả định)
        return $this->getQuery()
            ->join('driver_profiles', 'rides.driver_id', '=', 'driver_profiles.user_id')
            ->whereBetween('rides.completed_at', [$start, $end])
            ->where('rides.status', RideStatus::COMPLETED->value)
            ->select([
                'driver_profiles.driver_group_type',
                DB::raw("SUM(rides.service_fee) as system_commission"),
                DB::raw("COUNT(*) as total_rides"),
                DB::raw("SUM(rides.total_price) as gmv")
            ])
            ->groupBy('driver_profiles.driver_group_type')
            ->get()
            ->map(function($item) {
                $item->group_label = $item->driver_group_type == 1 ? 'Đội xe nhà' : 'Đối tác ngoài';
                return $item;
            })
            ->toArray();
    }

    /**
     * @inheritDoc
     */
    public function getCommissionDetails(CarbonInterface $start, CarbonInterface $end, int $limit = 50): array
    {
        return $this->getQuery()
            ->join('driver_profiles', 'rides.driver_id', '=', 'driver_profiles.user_id')
            ->whereBetween('rides.completed_at', [$start, $end])
            ->where('rides.status', RideStatus::COMPLETED->value)
            ->select([
                'rides.id as ride_id',
                'driver_profiles.full_name as driver_name',
                'rides.vehicle_type',
                'rides.total_price as total_amount',
                'rides.service_fee as commission_amount',
                DB::raw("CASE WHEN rides.total_price > 0 THEN (rides.service_fee / rides.total_price * 100) ELSE 0 END as commission_percent"),
                'rides.completed_at',
                'rides.is_paid as payment_status'
            ])
            ->orderByDesc('rides.completed_at')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * @inheritDoc
     */
    public function getVehicleTypeAnalytics(CarbonInterface $start, CarbonInterface $end): array
    {
        return $this->getQuery()
            ->whereBetween('created_at', [$start, $end])
            ->where('status', RideStatus::COMPLETED->value)
            ->select([
                'vehicle_type',
                DB::raw("COUNT(*) as total_rides"),
                DB::raw("SUM(total_price) as total_revenue")
            ])
            ->groupBy('vehicle_type')
            ->get()
            ->toArray();
    }

    /**
     * @inheritDoc
     */
    public function getRideTypeAnalytics(CarbonInterface $start, CarbonInterface $end): array
    {
        return $this->getQuery()
            ->whereBetween('created_at', [$start, $end])
            ->where('status', RideStatus::COMPLETED->value)
            ->select([
                'ride_type',
                DB::raw("COUNT(*) as total_rides"),
                DB::raw("SUM(total_price) as total_revenue")
            ])
            ->groupBy('ride_type')
            ->get()
            ->toArray();
    }

    /**
     * @inheritDoc
     */
    /**
     * @inheritDoc
     */
    public function getTopDriversAnalytics(CarbonInterface $start, CarbonInterface $end, int $limit = 10, ?int $driverGroupType = null): array
    {
        $query = $this->getQuery()
            ->join('driver_profiles', 'rides.driver_id', '=', 'driver_profiles.user_id')
            ->whereBetween('rides.created_at', [$start, $end])
            ->where('rides.status', RideStatus::COMPLETED->value);

        if ($driverGroupType !== null) {
            $query->where('driver_profiles.driver_group_type', $driverGroupType);
        }

        return $query->select([
            'rides.driver_id',
            'driver_profiles.full_name as driver_name',
            'driver_profiles.driver_group_type',
            DB::raw("COUNT(rides.id) as total_rides"),
            DB::raw("SUM(rides.total_price) as total_revenue")
        ])
        ->groupBy('rides.driver_id', 'driver_profiles.full_name', 'driver_profiles.driver_group_type')
        ->orderByRaw('SUM(rides.total_price) DESC')
        ->limit($limit)
        ->get()
        ->toArray();
    }

    /**
     * @inheritDoc
     */
    public function listDeliveryRidesForAdmin(array $excludeRideIds): \Illuminate\Support\Collection
    {
        $query = $this->getQuery()
            ->where('ride_type', \App\Modules\Ride\Model\Enums\RideType::DELIVERY->value)
            ->with(['customer.customerProfile', 'driver.driverProfile']);

        if (!empty($excludeRideIds)) {
            $query->whereNotIn('id', $excludeRideIds);
        }

        return $query->latest()->get();
    }

    /**
     * @inheritDoc
     */
    public function getDriverRides(string $driverId, ?array $statuses = null, int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = $this->getQuery()
            ->with(['customer'])
            ->whereRaw('driver_id = ?::bigint', [(string) $driverId]);

        if ($statuses !== null) {
            $query->whereIn('status', $statuses);
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }
}

