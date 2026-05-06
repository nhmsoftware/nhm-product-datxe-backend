<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Pricing\DTO\UpdateScheduledPricingDTO;
use App\Modules\Pricing\Http\Requests\AdminScheduledPricingRequest;
use App\Modules\Pricing\Interfaces\ScheduledPricingServiceInterface;
use Illuminate\Http\JsonResponse;

final class AdminScheduledPricingController extends BaseController
{
    public function __construct(
        private readonly ScheduledPricingServiceInterface $service
    ) {}

    /**
     * UC-121: Lấy cấu hình giá đặt trước hiện tại
     */
    public function show(): JsonResponse
    {
        $result = $this->service->getCurrentSettings();
        return $this->sendSuccess($result->getData(), 'Lấy cấu hình giá đặt trước thành công.');
    }

    /**
     * UC-121: Cập nhật cấu hình giá đặt trước
     */
    public function update(AdminScheduledPricingRequest $request): JsonResponse
    {
        $dto = UpdateScheduledPricingDTO::fromRequest($request);
        $result = $this->service->updateSettings($dto);

        if (!$result->isSuccess()) {
            return $this->sendError($result->getMessage(), $result->getStatusCode());
        }

        return $this->sendSuccess($result->getData(), 'Cập nhật cấu hình giá thành công.');
    }
}
