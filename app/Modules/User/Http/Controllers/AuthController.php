<?php

declare(strict_types=1);

namespace App\Modules\User\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\User\Http\Requests\Auth\LoginRequest;
use App\Modules\User\Http\Requests\Auth\RegisterRequest;
use App\Modules\User\Http\Requests\Auth\SendOtpRequest;
use App\Modules\User\Http\Requests\Auth\VerifyOtpRequest;
use App\Modules\User\Http\Resources\AuthResource;
use App\Modules\User\Http\Resources\UserResource;
use App\Modules\User\Interfaces\AuthServiceInterface;
use App\Modules\User\Model\Enums\UserOtpType;
use App\Modules\User\Model\Enums\UserRole;
use App\Modules\User\Services\Auth\LoginData;
use App\Modules\User\Services\Auth\RegisterData;
use App\Modules\User\Services\Auth\SendOtpData;
use App\Modules\User\Services\Auth\VerifyOtpData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AuthController extends BaseController
{
    public function __construct(
        protected AuthServiceInterface $authService,
//        private readonly RegisterService  $registerService,
//        private readonly LoginService     $loginService,
//        private readonly LogoutService    $logoutService,
//        private readonly SendOtpService   $sendOtpService,
//        private readonly VerifyOtpService $verifyOtpService,
    ) {}

    #[OA\Post(
        path: 'api/v1/auth/send-otp',
        summary: 'Gửi mã OTP',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['phone', 'type'],
                properties: [
                    new OA\Property(property: 'phone', type: 'string',  example: '0901234567'),
                    new OA\Property(property: 'type', description: '1=Verify_Register, 2=Verify_Forgot_Password',  type: 'integer', example: 1),
                ]
            )
        ),
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 200, description: 'Gửi thành công'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 429, description: 'Gửi quá nhiều lần'),
        ]
    )]
    public function sendOtp(SendOtpRequest $request): JsonResponse
    {
        $data = $request->validated();

        $result = $this->authService->sendOtp(
            phone: $data['phone'],
            type: UserOtpType::from($data['type']),
        );

        if ($result->isError()){
            return $this->sendError(
                message: $result->getMessage(),
                code: $result->getCode(),
            );
        }
        $data = $result->getData();
        return $this->sendSuccess(
            data: [
                /// ....
            ],
            message: $result->getMessage(),
        );

    }

    // ─────────────────────────────────────────────────────────────
    // POST /api/auth/verify-otp
    // ─────────────────────────────────────────────────────────────
    #[OA\Post(
        path: '/api/auth/verify-otp',
        summary: 'Xác minh mã OTP',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['phone', 'otp', 'type'],
                properties: [
                    new OA\Property(property: 'phone', type: 'string',  example: '0901234567'),
                    new OA\Property(property: 'otp',   type: 'string',  example: '123456'),
                    new OA\Property(property: 'type',  type: 'integer', example: 1),
                ]
            )
        ),
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 200, description: 'OTP hợp lệ'),
            new OA\Response(response: 400, description: 'OTP sai / hết hạn'),
        ]
    )]
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $this->verifyOtpService->handle(
            VerifyOtpData::fromArray($request->validated())
        );

        return response()->json(['message' => 'Xác minh OTP thành công.']);
    }

    // ─────────────────────────────────────────────────────────────
    // POST /api/auth/register
    // ─────────────────────────────────────────────────────────────
    #[OA\Post(
        path: '/api/auth/register',
        summary: 'Đăng ký tài khoản khách hàng',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['phone', 'password', 'password_confirmation', 'full_name'],
                properties: [
                    new OA\Property(property: 'phone',                 type: 'string', example: '0901234567'),
                    new OA\Property(property: 'password',              type: 'string', example: 'Secret@123'),
                    new OA\Property(property: 'password_confirmation', type: 'string', example: 'Secret@123'),
                    new OA\Property(property: 'full_name',             type: 'string', example: 'Nguyễn Văn A'),
                    new OA\Property(property: 'device_id',             type: 'string', example: 'abc123'),
                    new OA\Property(property: 'device_token',          type: 'string', example: 'fcm_token_here'),
                    new OA\Property(property: 'device_type',           type: 'string', example: 'android'),
                ]
            )
        ),
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 201, description: 'Đăng ký thành công'),
            new OA\Response(response: 409, description: 'Số điện thoại đã tồn tại'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->registerService->handle(
            RegisterData::fromArray([
                ...$request->validated(),
                'role' => UserRole::Customer->value,
            ])
        );

        return (new AuthResource($result['user']))
            ->withToken($result['token'])
            ->response()
            ->setStatusCode(201);
    }

    // ─────────────────────────────────────────────────────────────
    // POST /api/auth/login
    // ─────────────────────────────────────────────────────────────
    #[OA\Post(
        path: '/api/auth/login',
        summary: 'Đăng nhập',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['phone', 'password'],
                properties: [
                    new OA\Property(property: 'phone',        type: 'string', example: '0901234567'),
                    new OA\Property(property: 'password',     type: 'string', example: 'Secret@123'),
                    new OA\Property(property: 'device_id',    type: 'string'),
                    new OA\Property(property: 'device_token', type: 'string'),
                    new OA\Property(property: 'device_type',  type: 'string'),
                ]
            )
        ),
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 200, description: 'Đăng nhập thành công'),
            new OA\Response(response: 401, description: 'Sai thông tin đăng nhập'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->loginService->handle(
            LoginData::fromArray($request->validated())
        );

        return (new AuthResource($result['user']))
            ->withToken($result['token'])
            ->response();
    }

    // ─────────────────────────────────────────────────────────────
    // GET /api/auth/me  [auth:sanctum]
    // ─────────────────────────────────────────────────────────────
    #[OA\Get(
        path: '/api/auth/me',
        summary: 'Thông tin người dùng hiện tại',
        security: [['sanctum' => []]],
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 200, description: 'OK'),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
        ]
    )]
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(
            $request->user()->isCustomer() ? 'customerProfile' : 'driverProfile'
        );

        return (new UserResource($user))->response();
    }

    // ─────────────────────────────────────────────────────────────
    // POST /api/auth/logout  [auth:sanctum]
    // ─────────────────────────────────────────────────────────────
    #[OA\Post(
        path: '/api/auth/logout',
        summary: 'Đăng xuất',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: 'logout_all',
                        description: 'true = thu hồi tất cả token trên mọi thiết bị',
                        type: 'boolean',
                        example: false,
                    ),
                ]
            )
        ),
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 200, description: 'Đăng xuất thành công'),
        ]
    )]
    public function logout(Request $request): JsonResponse
    {
        $this->logoutService->handle(
            $request->user(),
            (bool) $request->input('logout_all', false)
        );

        return response()->json(['message' => 'Đăng xuất thành công.']);
    }
}
