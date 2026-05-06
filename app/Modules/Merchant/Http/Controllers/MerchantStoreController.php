<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Merchant\Interfaces\MerchantStoreServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class MerchantStoreController extends BaseController
{
    public function __construct(
        private readonly MerchantStoreServiceInterface $storeService
    ) {}

    #[OA\Get(path: '/api/v1/merchant/store', summary: 'Lấy thông tin cửa hàng (UC-53)', tags: ['Merchant'])]
    public function getInfo(Request $request): JsonResponse
    {
        $result = $this->storeService->getStoreInfo((string)$request->user()->id);
        if ($result->isError()) return $this->sendError($result->getMessage(), $result->getCode());
        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Put(path: '/api/v1/merchant/store/status', summary: 'Cập nhật trạng thái đóng/mở (UC-46)', tags: ['Merchant'])]
    public function updateStatus(Request $request): JsonResponse
    {
        $request->validate(['is_open' => ['required', 'boolean']]);
        $result = $this->storeService->updateStatus((string)$request->user()->id, (bool)$request->input('is_open'));
        if ($result->isError()) return $this->sendError($result->getMessage(), $result->getCode());
        return $this->sendSuccess($result->getData(), 'Cập nhật trạng thái thành công.');
    }

    #[OA\Put(path: '/api/v1/merchant/store/hours', summary: 'Thiết lập giờ mở cửa (Cơ bản) (UC-45)', tags: ['Merchant'])]
    public function updateHours(Request $request): JsonResponse
    {
        $request->validate([
            'opening_time' => ['required', 'date_format:H:i'],
            'closing_time' => ['required', 'date_format:H:i'],
        ]);
        $result = $this->storeService->updateOperatingHours(
            (string)$request->user()->id,
            $request->input('opening_time'),
            $request->input('closing_time')
        );
        if ($result->isError()) return $this->sendError($result->getMessage(), $result->getCode());
        return $this->sendSuccess($result->getData(), 'Cập nhật giờ hoạt động thành công.');
    }

    #[OA\Put(path: '/api/v1/merchant/store/weekly-hours', summary: 'Thiết lập giờ mở cửa chi tiết theo tuần (UC-54)', tags: ['Merchant'])]
    public function updateWeeklyHours(Request $request): JsonResponse
    {
        $request->validate([
            'schedule'              => ['required', 'array', 'min:1', 'max:7'],
            'schedule.*.day_of_week' => ['required', 'integer', 'min:1', 'max:7'],
            'schedule.*.is_closed'   => ['required', 'boolean'],
            'schedule.*.opening_time'=> ['nullable', 'date_format:H:i', 'required_if:schedule.*.is_closed,false'],
            'schedule.*.closing_time'=> ['nullable', 'date_format:H:i', 'required_if:schedule.*.is_closed,false'],
        ]);

        $result = $this->storeService->updateWeeklySchedule(
            (string)$request->user()->id,
            $request->input('schedule')
        );

        if ($result->isError()) return $this->sendError($result->getMessage(), $result->getCode());
        return $this->sendSuccess($result->getData(), 'Cập nhật giờ hoạt động hàng tuần thành công.');
    }

    #[OA\Put(path: '/api/v1/merchant/store/discount', summary: 'Cấu hình chiết khấu (Cơ bản) (UC-47)', tags: ['Merchant'])]
    public function updateDiscount(Request $request): JsonResponse
    {
        $request->validate(['commission_rate' => ['required', 'numeric', 'min:0', 'max:100']]);
        $result = $this->storeService->updateDiscount(
            (string)$request->user()->id,
            (float)$request->input('commission_rate')
        );
        if ($result->isError()) return $this->sendError($result->getMessage(), $result->getCode());
        return $this->sendSuccess($result->getData(), 'Cập nhật chiết khấu thành công.');
    }

    #[OA\Get(path: '/api/v1/merchant/store/commission-packages', summary: 'Lấy danh sách gói chiết khấu (UC-56)', tags: ['Merchant'])]
    public function getPackages(): JsonResponse
    {
        $packages = $this->storeService->getCommissionPackages();
        return $this->sendSuccess($packages, 'Tải danh sách gói chiết khấu thành công.');
    }

    #[OA\Put(path: '/api/v1/merchant/store/commission-package', summary: 'Thay đổi gói chiết khấu (UC-56)', tags: ['Merchant'])]
    public function updatePackage(Request $request): JsonResponse
    {
        $request->validate([
            'package_key' => ['required', 'string'],
        ]);

        $result = $this->storeService->updateCommissionPackage(
            (string)$request->user()->id,
            $request->input('package_key')
        );

        if ($result->isError()) return $this->sendError($result->getMessage(), $result->getCode());
        return $this->sendSuccess($result->getData(), $result->getMessage());
    }
}
