<?php

declare(strict_types=1);

namespace App\Modules\Ride\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Ride\Interfaces\RideRepositoryInterface;
use App\Modules\Ride\Model\Enums\RideStatus;
use App\Modules\Ride\Model\Ride;
use Carbon\CarbonInterface;

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
        /** @var Ride|null */
        return $this->model->where('id', $rideId)
            ->where('customer_id', $customerId)
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
            'status'    => RideStatus::ACCEPTED->value,
            'driver_id' => $driverId,
        ]);
    }

    /**
     * Tài xế từ chối nhận đơn (UC-33 Reject).
     */
    public function rejectByDriver(string $rideId, string $driverId): bool
    {
        return DB::table('ride_rejects')->updateOrInsert(
            ['ride_id' => $rideId, 'driver_id' => $driverId],
            ['updated_at' => now(), 'created_at' => now()]
        );
    }

    /**
     * Tài xế hủy chuyến sau khi đã nhận (UC-33 Cancel).
     */
    public function cancelByDriver(string $rideId, string $reasonId): bool
    {
        return (bool) $this->model->where('id', $rideId)->update([
            'status'        => RideStatus::CANCELLED->value,
            'cancel_reason' => (string) $reasonId, // Lưu ID lý do hoặc map sang label
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
        return DB::table('ride_rejects')
            ->where('ride_id', $rideId)
            ->where('driver_id', $driverId)
            ->exists();
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
        /** @var Ride|null */
        return $this->model->with(['driver.driverProfile'])
            ->where('id', $rideId)
            ->where('customer_id', $customerId)
            ->first();
    }

    public function assignDriver(string $rideId, string $driverId, CarbonInterface $acceptedAt): bool
    {
        return (bool) $this->model->where('id', $rideId)->update([
            'status' => RideStatus::ACCEPTED->value,
            'driver_id' => $driverId,
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
        ]);
    }

    public function releaseDriverFromRide(string $rideId, ?string $reason): bool
    {
        return (bool) $this->model->where('id', $rideId)->update([
            'status' => RideStatus::PENDING->value,
            'driver_id' => null,
            'driver_assigned_at' => null,
            'driver_arrived_at' => null,
            'cancel_reason' => $reason,
        ]);
    }
}
