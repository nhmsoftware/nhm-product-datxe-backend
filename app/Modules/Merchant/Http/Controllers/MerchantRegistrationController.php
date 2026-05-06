<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Merchant\DTO\RegisterMerchantDTO;
use App\Modules\Merchant\Http\Requests\RegisterMerchantRequest;
use App\Modules\Merchant\Interfaces\MerchantRegistrationServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class MerchantRegistrationController extends BaseController
{
    public function __construct(
        private readonly MerchantRegistrationServiceInterface $registrationService
    ) {}

    #[OA\Post(path: '/api/v1/merchant/register', summary: 'Gửi đăng ký Merchant (UC-52)', tags: ['Merchant'])]
    #[OA\Response(response: 200, description: 'Đăng ký thành công')]
    public function register(RegisterMerchantRequest $request): JsonResponse
    {
        $result = $this->registrationService->submitRegistration(RegisterMerchantDTO::fromRequest($request));
        
        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Post(path: '/api/v1/merchant/send-otp', summary: 'Gửi OTP đăng ký Merchant (UC-52)', tags: ['Merchant'])]
    public function sendOtp(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => ['required', 'string', 'regex:/^([0-9\s\-\+\(\)]*)$/', 'min:10'],
        ]);

        $result = $this->registrationService->sendOtp((string)$request->user()->id, $request->input('phone'));

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Post(path: '/api/v1/merchant/verify-otp', summary: 'Xác thực OTP đăng ký Merchant (UC-52)', tags: ['Merchant'])]
    public function verifyOtp(Request $request): JsonResponse
    {
        $request->validate([
            'otp' => ['required', 'string', 'size:6'],
        ]);

        $result = $this->registrationService->verifyOtp((string)$request->user()->id, $request->input('otp'));

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess(null, 'Xác thực OTP thành công.');
    }
}
