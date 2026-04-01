<?php

declare(strict_types=1);

namespace Modules\User\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\User\Application\Actions\Auth\LoginAction;
use Modules\User\Application\Actions\Auth\LogoutAction;
use Modules\User\Application\Actions\Auth\RegisterAction;
use Modules\User\Application\Actions\Auth\SendOtpAction;
use Modules\User\Application\Actions\Auth\VerifyOtpAction;
use Modules\User\Application\DTOs\Auth\LoginDTO;
use Modules\User\Application\DTOs\Auth\RegisterDTO;
use Modules\User\Application\DTOs\Auth\SendOtpDTO;
use Modules\User\Application\DTOs\Auth\VerifyOtpDTO;
use Modules\User\Domain\Enums\UserRole;
use Modules\User\Presentation\Requests\Auth\LoginRequest;
use Modules\User\Presentation\Requests\Auth\RegisterRequest;
use Modules\User\Presentation\Requests\Auth\SendOtpRequest;
use Modules\User\Presentation\Requests\Auth\VerifyOtpRequest;
use Modules\User\Presentation\Resources\Auth\AuthResource;
use Modules\User\Presentation\Resources\Auth\UserResource;

class AuthController extends Controller
{
    public function __construct(
        private readonly RegisterAction  $registerAction,
        private readonly LoginAction     $loginAction,
        private readonly LogoutAction    $logoutAction,
        private readonly SendOtpAction   $sendOtpAction,
        private readonly VerifyOtpAction $verifyOtpAction,
    ) {}

    // ─────────────────────────────────────────────────────────────
    // POST /api/auth/send-otp
    // ─────────────────────────────────────────────────────────────
    /**
     * @OA\Post(
     *   path="/api/auth/send-otp",
     *   summary="Gửi mã OTP",
     *   tags={"Auth"},
     *   @OA\RequestBody(required=true, @OA\JsonContent(
     *     required={"phone","type"},
     *     @OA\Property(property="phone",  type="string", example="0901234567"),
     *     @OA\Property(property="type",   type="integer", example=1,
     *       description="1=Verify_Register, 2=Verify_Forgot_Password")
     *   )),
     *   @OA\Response(response=200, description="Gửi thành công"),
     *   @OA\Response(response=429, description="Gửi quá nhiều lần"),
     *   @OA\Response(response=422, description="Validation error")
     * )
     */
    public function sendOtp(SendOtpRequest $request): JsonResponse
    {
        $this->sendOtpAction->execute(
            SendOtpDTO::fromArray([
                ...$request->validated(),
                'ip_address' => $request->ip(),
            ])
        );

        return response()->json([
            'message' => 'Mã OTP đã được gửi tới số điện thoại của bạn.',
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // POST /api/auth/verify-otp
    // ─────────────────────────────────────────────────────────────
    /**
     * @OA\Post(
     *   path="/api/auth/verify-otp",
     *   summary="Xác minh mã OTP",
     *   tags={"Auth"},
     *   @OA\RequestBody(required=true, @OA\JsonContent(
     *     required={"phone","otp","type"},
     *     @OA\Property(property="phone", type="string", example="0901234567"),
     *     @OA\Property(property="otp",   type="string", example="123456"),
     *     @OA\Property(property="type",  type="integer", example=1)
     *   )),
     *   @OA\Response(response=200, description="OTP hợp lệ"),
     *   @OA\Response(response=400, description="OTP sai / hết hạn")
     * )
     */
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $this->verifyOtpAction->execute(
            VerifyOtpDTO::fromArray($request->validated())
        );

        return response()->json(['message' => 'Xác minh OTP thành công.']);
    }

    // ─────────────────────────────────────────────────────────────
    // POST /api/auth/register
    // ─────────────────────────────────────────────────────────────
    /**
     * @OA\Post(
     *   path="/api/auth/register",
     *   summary="Đăng ký tài khoản khách hàng",
     *   tags={"Auth"},
     *   @OA\RequestBody(required=true, @OA\JsonContent(
     *     required={"phone","password","password_confirmation","full_name"},
     *     @OA\Property(property="phone",                 type="string",  example="0901234567"),
     *     @OA\Property(property="password",              type="string",  example="Secret@123"),
     *     @OA\Property(property="password_confirmation", type="string",  example="Secret@123"),
     *     @OA\Property(property="full_name",             type="string",  example="Nguyễn Văn A"),
     *     @OA\Property(property="device_id",             type="string",  example="abc123"),
     *     @OA\Property(property="device_token",          type="string",  example="fcm_token_here"),
     *     @OA\Property(property="device_type",           type="string",  example="android")
     *   )),
     *   @OA\Response(response=201, description="Đăng ký thành công"),
     *   @OA\Response(response=409, description="Số điện thoại đã tồn tại"),
     *   @OA\Response(response=422, description="Validation error")
     * )
     */
    public function register(RegisterRequest $request): JsonResponse
    {

        dd($request);

       $result = $this->registerAction->execute(
           RegisterDTO::fromArray([
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
    /**
     * @OA\Post(
     *   path="/api/auth/login",
     *   summary="Đăng nhập",
     *   tags={"Auth"},
     *   @OA\RequestBody(required=true, @OA\JsonContent(
     *     required={"phone","password"},
     *     @OA\Property(property="phone",        type="string", example="0901234567"),
     *     @OA\Property(property="password",     type="string", example="Secret@123"),
     *     @OA\Property(property="device_id",    type="string"),
     *     @OA\Property(property="device_token", type="string"),
     *     @OA\Property(property="device_type",  type="string")
     *   )),
     *   @OA\Response(response=200, description="Đăng nhập thành công"),
     *   @OA\Response(response=401, description="Sai thông tin đăng nhập"),
     *   @OA\Response(response=422, description="Validation error")
     * )
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->loginAction->execute(
            LoginDTO::fromArray($request->validated())
        );

        return (new AuthResource($result['user']))
            ->withToken($result['token'])
            ->response();
    }

    // ─────────────────────────────────────────────────────────────
    // GET /api/auth/me  [auth:sanctum]
    // ─────────────────────────────────────────────────────────────
    /**
     * @OA\Get(
     *   path="/api/auth/me",
     *   summary="Thông tin người dùng hiện tại",
     *   tags={"Auth"},
     *   security={{"sanctum":{}}},
     *   @OA\Response(response=200, description="OK"),
     *   @OA\Response(response=401, description="Chưa đăng nhập")
     * )
     */
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
    /**
     * @OA\Post(
     *   path="/api/auth/logout",
     *   summary="Đăng xuất",
     *   tags={"Auth"},
     *   security={{"sanctum":{}}},
     *   @OA\RequestBody(@OA\JsonContent(
     *     @OA\Property(property="logout_all", type="boolean", example=false,
     *       description="true = thu hồi tất cả token trên mọi thiết bị")
     *   )),
     *   @OA\Response(response=200, description="Đăng xuất thành công")
     * )
     */
    public function logout(Request $request): JsonResponse
    {
        $this->logoutAction->execute(
            $request->user(),
            (bool) $request->input('logout_all', false)
        );

        return response()->json(['message' => 'Đăng xuất thành công.']);
    }
}
