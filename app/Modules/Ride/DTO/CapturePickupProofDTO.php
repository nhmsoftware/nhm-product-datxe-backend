<?php

declare(strict_types=1);

namespace App\Modules\Ride\DTO;

use App\Modules\Ride\Http\Requests\CapturePickupProofRequest;
use Illuminate\Http\UploadedFile;

/**
 * UC-37: Capture Pickup Proof DTO
 * Chứa toàn bộ thông tin cần thiết để lưu bằng chứng lấy hàng.
 */
final class CapturePickupProofDTO
{
    public function __construct(
        /** ID chuyến xe (lấy từ URL param) */
        public readonly string $rideId,
        /** ID tài xế (lấy từ auth) */
        public readonly string $driverId,
        /** Ảnh xác nhận (nullable nếu A3/A6 — driver không chụp được) */
        public readonly ?UploadedFile $photo,
        /** Vị trí GPS tại thời điểm chụp */
        public readonly ?float $capturedLat,
        public readonly ?float $capturedLng,
        /** A3/A6: Lý do bỏ qua chụp ảnh (merchant_refused, device_error, other) */
        public readonly ?string $skipReason,
        /** A3/A6: Ghi chú thêm nếu bỏ qua */
        public readonly ?string $note,
    ) {}

    public static function fromRequest(CapturePickupProofRequest $request): self
    {
        return new self(
            rideId:      (string) $request->route('rideId'),
            driverId:    (string) $request->user()->id,
            photo:       $request->file('photo'),
            capturedLat: $request->input('captured_lat') !== null ? (float) $request->input('captured_lat') : null,
            capturedLng: $request->input('captured_lng') !== null ? (float) $request->input('captured_lng') : null,
            skipReason:  $request->input('skip_reason'),
            note:        $request->input('note'),
        );
    }
}
