<?php

declare(strict_types=1);

namespace App\Modules\Ride\DTO;

use App\Modules\Ride\Http\Requests\CaptureDeliveryProofRequest;
use Illuminate\Http\UploadedFile;

/**
 * UC-38: Capture Delivery Proof DTO
 */
final class CaptureDeliveryProofDTO
{
    public function __construct(
        public readonly string $rideId,
        public readonly string $driverId,
        public readonly ?UploadedFile $photo,
        public readonly ?float $capturedLat,
        public readonly ?float $capturedLng,
        public readonly ?string $skipReason,
        public readonly ?string $note,
    ) {}

    public static function fromRequest(CaptureDeliveryProofRequest $request): self
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
