<?php

declare(strict_types=1);

namespace App\Modules\Ride\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Ride\Interfaces\RideRepositoryInterface;
use App\Modules\Ride\Model\Enums\RideStatus;
use App\Modules\Ride\Model\Ride;

final class RideRepository extends BaseRepository implements RideRepositoryInterface
{
    public function getModel(): string
    {
        return Ride::class;
    }

    /**
     * Tìm ride draft theo ID và customer ID để xác thực quyền sở hữu.
     */
    public function findByIdAndCustomer(int $rideId, int $customerId): ?Ride
    {
        /** @var Ride|null */
        return Ride::where('id', $rideId)
            ->where('customer_id', $customerId)
            ->first();
    }

    /**
     * Áp dụng voucher vào chuyến đi — lưu mã, discount và giá cuối (UC-11).
     */
    public function applyVoucher(int $rideId, string $voucherCode, float $discountAmount, float $finalPrice): bool
    {
        return (bool) Ride::where('id', $rideId)->update([
            'voucher_code'    => $voucherCode,
            'discount_amount' => $discountAmount,
            'total_price'     => $finalPrice,
        ]);
    }

    /**
     * Xóa voucher khỏi chuyến đi, khôi phục giá gốc (UC-11 A4).
     */
    public function clearVoucher(int $rideId, float $originalPrice): bool
    {
        return (bool) Ride::where('id', $rideId)->update([
            'voucher_code'    => null,
            'discount_amount' => 0,
            'total_price'     => $originalPrice,
        ]);
    }

    /**
     * Xác nhận đặt xe, chuyển trạng thái sang PENDING và chốt giá (UC-12).
     */
    public function confirmBooking(int $rideId, float $finalPrice): bool
    {
        return (bool) Ride::where('id', $rideId)->update([
            'status'      => RideStatus::PENDING->value,
            'total_price' => $finalPrice,
        ]);
    }

    /**
     * Hủy chuyến đi, cập nhật lý do và phí hủy nếu có (UC-15).
     */
    public function cancel(int $rideId, ?string $reason, float $cancellationFee): bool
    {
        return (bool) Ride::where('id', $rideId)->update([
            'status'           => RideStatus::CANCELLED->value,
            'cancel_reason'    => $reason,
            'cancellation_fee' => $cancellationFee,
        ]);
    }
}
