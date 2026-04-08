<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Auth\Http\Requests\AppleLoginRequest;
use App\Modules\Auth\Http\Requests\GoogleLoginRequest;
use App\Modules\Auth\Http\Requests\LoginRequest;
use App\Modules\Auth\Http\Requests\RegisterRequest;
use App\Modules\Auth\Http\Requests\ResetPasswordRequest;
use App\Modules\Auth\Http\Requests\SendOtpRequest;
use App\Modules\Auth\Http\Resources\AuthResource;
use App\Modules\Auth\Interfaces\AuthServiceInterface;
use App\Modules\User\Model\Enums\UserOtpType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AuthController extends BaseController
{
    public function __construct(
        protected AuthServiceInterface $authService,
    ) {}


    #[OA\Post(
        path: '/api/v1/auth/authenticate-otp',
        summary: 'Gửi mã OTP',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['phone', 'type'],
                properties: [
                    new OA\Property(property: 'phone', type: 'string',  example: '0901234567'),
                    new OA\Property(
                        property: 'type',
                        description: '1=Đăng ký, 2=Đăng nhập, 3=Quên mật khẩu',
                        type: 'integer',
                        example: 1,
                    ),
                ]
            )
        ),
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 200, description: 'Gửi OTP thành công'),
            new OA\Response(response: 404, description: 'Số điện thoại không tồn tại (type=2)'),
            new OA\Response(response: 409, description: 'Số điện thoại đã đăng ký (type=1)'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 429, description: 'Gửi quá nhiều lần'),
        ]
    )]
    public function authenticateOtp(SendOtpRequest $request): JsonResponse
    {
        $data = $request->validated();

        $result = $this->authService->sendOtp(
            phone: $data['phone'],
            type:  UserOtpType::from((int) $data['type']),
        );

        if ($result->isError()) {
            return $this->sendError(
                message: $result->getMessage(),
                code:    $result->getCode(),
            );
        }

        return $this->sendSuccess(
            data:    $result->getData(),
            message: $result->getMessage(),
        );
    }

    #[OA\Post(
        path: '/api/v1/auth/register',
        summary: 'Xác minh OTP và đăng ký tài khoản',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['phone', 'otp', 'full_name'],
                properties: [
                    new OA\Property(property: 'phone',        type: 'string', example: '0901234567'),
                    new OA\Property(property: 'otp',          type: 'string', example: '123456'),
                    new OA\Property(property: 'full_name',    type: 'string', example: 'Nguyễn Văn A'),
                    new OA\Property(property: 'device_id',    type: 'string', example: 'abc123'),
                    new OA\Property(property: 'device_token', type: 'string', example: 'fcm_token_here'),
                    new OA\Property(property: 'device_type',  type: 'string', example: 'android'),
                ]
            )
        ),
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 201, description: 'Đăng ký thành công, trả về token'),
            new OA\Response(response: 400, description: 'OTP sai hoặc hết hạn'),
            new OA\Response(response: 409, description: 'Số điện thoại đã tồn tại'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();
        $result = $this->authService->register($data);

        if ($result->isError()) {
            return $this->sendError(
                message: $result->getMessage(),
                code:    $result->getCode(),
            );
        }
        $data = $result->getData();
        return $this->sendSuccess(
            data:    [
                'user' => new AuthResource($data['user']),
                'token' => $data['token'],
            ],
            message: $result->getMessage(),
            code: 201,
        );
    }

    #[OA\Post(
        path: '/api/v1/auth/login',
        summary: 'Đăng nhập bằng số điện thoại và mật khẩu',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['phone', 'password'],
                properties: [
                    new OA\Property(property: 'phone',        type: 'string', example: '0901234567'),
                    new OA\Property(property: 'password',     type: 'string', example: 'Password123!'),
                    new OA\Property(property: 'device_id',    type: 'string'),
                    new OA\Property(property: 'device_token', type: 'string'),
                    new OA\Property(property: 'device_type',  type: 'string'),
                ]
            )
        ),
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 200, description: 'Đăng nhập thành công'),
            new OA\Response(response: 401, description: 'Mật khẩu không đúng'),
            new OA\Response(response: 403, description: 'Tài khoản đã bị khóa'),
            new OA\Response(response: 404, description: 'Số điện thoại chưa đăng ký'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());

        if ($result->isError()) {
            return $this->sendError(
                message: $result->getMessage(),
                code:    $result->getCode(),
            );
        }
        $data = $result->getData();
        return $this->sendSuccess(
            data:    [
                'user' => new AuthResource($data['user']),
                'token' => $data['token'],
            ],
            message: $result->getMessage(),
        );
    }

    #[OA\Post(
        path: '/api/v1/auth/reset-password',
        summary: 'Đặt lại mật khẩu (Forgot Password)',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['phone', 'otp', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'phone', type: 'string', example: '0901234567'),
                    new OA\Property(property: 'otp', type: 'string', example: '123456'),
                    new OA\Property(property: 'password', type: 'string', example: 'NewPass123!'),
                    new OA\Property(property: 'password_confirmation', type: 'string', example: 'NewPass123!'),
                    new OA\Property(property: 'device_id', type: 'string'),
                    new OA\Property(property: 'device_token', type: 'string'),
                    new OA\Property(property: 'device_type', type: 'string'),
                ]
            )
        ),
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 200, description: 'Đặt lại mật khẩu thành công'),
            new OA\Response(response: 400, description: 'OTP sai hoặc hết hạn'),
            new OA\Response(response: 404, description: 'Số điện thoại chưa được đăng ký'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $result = $this->authService->resetPassword($request->validated());

        if ($result->isError()) {
            return $this->sendError(
                message: $result->getMessage(),
                code:    $result->getCode(),
            );
        }
        $data = $result->getData();
        return $this->sendSuccess(
            data:    [
                'user' => new AuthResource($data['user']),
                'token' => $data['token'],
            ],
            message: 'Đặt lại mật khẩu thành công',
        );
    }

    #[OA\Get(
        path: '/api/v1/auth/me',
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
        return $this->sendError(
            message: "Chưa có logic",
            code: 404,
        );
    }

    #[OA\Post(
        path: '/api/v1/auth/logout',
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
        $result = $this->authService->logout(
            user:      $request->user(),
            logoutAll: (bool) $request->input('logout_all', false),
        );

        if ($result->isError()) {
            return $this->sendError(
                message: $result->getMessage(),
                code:    $result->getCode(),
            );
        }

        return $this->sendSuccess(
            message: $result->getMessage()
        );
    }

    #[OA\Post(
        path: '/api/v1/auth/google-login',
        summary: 'Đăng nhập/đăng ký bằng Google',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['id_token'],
                properties: [
                    new OA\Property(property: 'id_token', type: 'string', example: 'google_id_token'),
                    new OA\Property(property: 'device_id', type: 'string'),
                    new OA\Property(property: 'device_token', type: 'string'),
                    new OA\Property(property: 'device_type', type: 'string', example: 'android'),
                ]
            )
        ),
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Đăng nhập/đăng ký thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'user'),
                                new OA\Property(property: 'token'),
                            ],
                            type: 'object'
                        )
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Token không hợp lệ'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function googleLogin(GoogleLoginRequest $request): JsonResponse
    {
        $result = $this->authService->googleLogin($request->validated());

        if ($result->isError()) {
            return $this->sendError(
                message: $result->getMessage(),
                code: $result->getCode(),
            );
        }

        $data = $result->getData();
        return $this->sendSuccess(
            data: [
                'user' => new AuthResource($data['user']),
                'token' => $data['token'],
            ],
            message: 'Đăng nhập Google thành công',
        );
    }

    #[OA\Post(
        path: '/api/v1/auth/apple-login',
        summary: 'Đăng nhập/đăng ký bằng Apple',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['id_token'],
                properties: [
                    new OA\Property(property: 'id_token', type: 'string', example: 'apple_id_token'),
                    new OA\Property(property: 'user', type: 'string'),
                    new OA\Property(property: 'device_id', type: 'string'),
                    new OA\Property(property: 'device_token', type: 'string'),
                    new OA\Property(property: 'device_type', type: 'string', example: 'ios'),
                ]
            )
        ),
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Đăng nhập/đăng ký thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'user'),
                                new OA\Property(property: 'token'),
                            ],
                            type: 'object'
                        )
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Token không hợp lệ'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function appleLogin(AppleLoginRequest $request): JsonResponse
    {
        $result = $this->authService->appleLogin($request->validated());

        if ($result->isError()) {
            return $this->sendError(
                message: $result->getMessage(),
                code: $result->getCode(),
            );
        }

        $data = $result->getData();
        return $this->sendSuccess(
            data: [
                'user' => new AuthResource($data['user']),
                'token' => $data['token'],
            ],
            message: 'Đăng nhập Apple thành công'
        );
    }


}
