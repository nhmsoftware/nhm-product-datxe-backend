<?php

declare(strict_types=1);

namespace App\Modules\Ride\DTO;

use Illuminate\Http\Request;

/**
 * UC-25: DTO cho yêu cầu tạo đơn giao hàng.
 */
final readonly class CreateDeliveryOrderDTO
{
    public function __construct(
        // Định danh khách hàng (lấy từ Auth trong Controller)
        public string $customerId,

        // Điểm lấy hàng
        public string $pickupAddress,
        public float  $pickupLat,
        public float  $pickupLng,

        // Điểm giao hàng
        public string $destinationAddress,
        public float  $destinationLat,
        public float  $destinationLng,

        // Loại xe (chỉ BIKE hoặc CAR_4_SEATS cho giao hàng)
        public int    $vehicleType,

        // Thông tin người gửi
        public string $senderName,
        public string $senderPhone,

        // Thông tin người nhận
        public string $receiverName,
        public string $receiverPhone,

        // Chi tiết hàng hóa
        public string  $goodsType,
        public float   $goodsWeight,
        public ?string $goodsNote   = null,
        public bool    $isFragile   = false,

        // Voucher (tuỳ chọn)
        public ?string $voucherCode = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            customerId:         (string) $request->user()->id,
            pickupAddress:      $request->input('pickup_address'),
            pickupLat:          (float) $request->input('pickup_lat'),
            pickupLng:          (float) $request->input('pickup_lng'),
            destinationAddress: $request->input('destination_address'),
            destinationLat:     (float) $request->input('destination_lat'),
            destinationLng:     (float) $request->input('destination_lng'),
            vehicleType:        (int) $request->input('vehicle_type', 1),
            senderName:         $request->input('sender_name'),
            senderPhone:        $request->input('sender_phone'),
            receiverName:       $request->input('receiver_name'),
            receiverPhone:      $request->input('receiver_phone'),
            goodsType:          $request->input('goods_type'),
            goodsWeight:        (float) $request->input('goods_weight'),
            goodsNote:          $request->input('goods_note'),
            isFragile:          (bool) $request->input('is_fragile', false),
            voucherCode:        $request->input('voucher_code'),
        );
    }
}
