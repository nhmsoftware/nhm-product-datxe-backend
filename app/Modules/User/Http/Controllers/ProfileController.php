<?php

declare(strict_types=1);

namespace App\Modules\User\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Core\Services\ServiceException;
use App\Modules\User\Http\Requests\ChangePasswordRequest;
use App\Modules\User\Http\Requests\EditProfileRequest;
use App\Modules\User\Http\Requests\VerifyOtpRequest;
use App\Modules\User\Http\Resources\ProfileResource;
use App\Modules\User\Interfaces\ProfileServiceInterface;
use Hash;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ProfileController extends BaseController
{
    public function __construct(
        private readonly ProfileServiceInterface $profileService
    ) {}

    /**
     * UC-04: View Profile
     *
     * Hiển thị thông tin cá nhân của người dùng đã đăng nhập.
     */
    #[OA\Get(
        path: '/api/v1/user/profile',
        description: 'Cho phép người dùng đã đăng nhập (Customer, Driver hoặc Merchant) xem thông tin tài khoản cá nhân của mình.',
        summary: 'UC-04: Xem thông tin hồ sơ',
        security: [['sanctum' => []]],
        tags: ['User Profile'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Thành công - Trả về thông tin hồ sơ',
                content: new OA\JsonContent(
                    required: ['success', 'data', 'message'],
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'object', ref: '#/components/schemas/ProfileResponse'),
                        new OA\Property(property: 'message', type: 'string', example: 'Lấy thông tin hồ sơ thành công.')
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Tài khoản bị khóa (A5)',
                content: new OA\JsonContent(
                    required: ['success', 'message'],
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ hỗ trợ.')
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Lỗi tải dữ liệu (A4)',
                content: new OA\JsonContent(
                    required: ['success', 'message'],
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Không thể tải thông tin hồ sơ. Vui lòng kiểm tra kết nối và thử lại.')
                    ]
                )
            )
        ]
    )]
    public function show(Request $request): JsonResponse
    {
        try {
            $serviceReturn = $this->profileService->getProfile($request->user());

            return $this->sendSuccess(
                data: (new ProfileResource($serviceReturn->getData()))->toArray($request),
                message: 'Lấy thông tin hồ sơ thành công.'
            );
        } catch (ServiceException $e) {
            return $this->sendError($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    /**
     * UC-05: Update Profile
     * Cập nhật thông tin cá nhân của người dùng. Nếu có thay đổi thông tin nhạy cảm (phone, email),
     * hệ thống sẽ yêu cầu xác thực OTP trước khi cập nhật.
     */
    #[OA\Put(
        path: '/api/v1/user/profile',
        description: "Cập nhật thông tin cá nhân của người dùng. Nếu thay đổi số điện thoại hoặc email, hệ thống sẽ yêu cầu xác thực OTP. Các trường driver-specific và merchant-specific sẽ được cập nhật tương ứng với vai trò.\n\n**Preconditions:** Người dùng đã đăng nhập. Tài khoản đang hoạt động. Token hợp lệ.\n\n**Postconditions:** Nếu thành công: Thông tin hồ sơ được cập nhật, trả về profile mới. Nếu có thay đổi nhạy cảm: Yêu cầu xác thực OTP qua endpoint /verify-otp.\n\n**Alternative Flows:**\nA1: Thay đổi số điện thoại → Gửi OTP đến số mới.\nA2: Thay đổi email → Gửi OTP xác thực.\nA5: Validation error → Trả về danh sách lỗi.\nA7: Lỗi server → Thông báo và cho thử lại.",
        summary: 'UC-05: Cập nhật thông tin hồ sơ',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
        ),
        tags: ['User Profile'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Thành công - Cập nhật thông tin thành công',
                content: new OA\JsonContent(
                    required: ['success', 'data', 'message'],
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'object', ref: '#/components/schemas/ProfileResponse'),
                        new OA\Property(property: 'message', type: 'string', example: 'Cập nhật thông tin thành công.')
                    ]
                )
            ),
            new OA\Response(
                response: 202,
                description: 'Yêu cầu xác thực OTP - Có thay đổi thông tin nhạy cảm (A1/A2)',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/OtpRequiredResponse'
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Tài khoản bị khóa',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/ForbiddenResponse'
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error (A5) - Dữ liệu không hợp lệ',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/ValidationErrorResponse'
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Lỗi server (A7) - Không thể cập nhật thông tin',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/ServerErrorResponse'
                )
            )
        ]
    )]
    public function update(EditProfileRequest $request): JsonResponse
    {
        try {
            $serviceReturn = $this->profileService->updateProfile($request->user(), $request->validated());

            return $this->sendSuccess(
                data: (new ProfileResource($serviceReturn->getData()))->toArray($request),
                message: 'Cập nhật thông tin thành công.'
            );
        } catch (ServiceException $e) {
            // Nếu service throw lỗi với code 422 (cần OTP), trả về response 202
            if ($e->getCode() === 422) {
                return $this->sendSuccess(
                    message: $e->getMessage(),
                    code: 202 // Accepted
                );
            }
            return $this->sendError($e->getMessage(), $e->getCode());
        }
    }

    /**
     * UC-05: Verify OTP for sensitive field changes
     * Xác thực OTP để cập nhật các thông tin nhạy cảm (số điện thoại, email).
     * Mã OTP có hiệu lực trong 5 phút và chỉ sử dụng được 1 lần.
     */
    #[OA\Post(
        path: '/api/v1/user/profile/verify-otp',
        description: 'Xác thực mã OTP để hoàn tất cập nhật thông tin nhạy cảm như số điện thoại hoặc email. Mã OTP được gửi qua SMS/email và có hiệu lực trong 5 phút.',
        summary: 'UC-05: Xác thực OTP cho thông tin nhạy cảm',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: '#/components/schemas/VerifyOtpProfileRequest'
            )
        ),
        tags: ['User Profile'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Thành công - Xác thực OTP và cập nhật thông tin thành công',
                content: new OA\JsonContent(
                    required: ['success', 'data', 'message'],
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'object', ref: '#/components/schemas/ProfileResponse'),
                        new OA\Property(property: 'message', type: 'string', example: 'Cập nhật thông tin thành công.')
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Tài khoản bị khóa',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/ForbiddenResponse'
                )
            ),
            new OA\Response(
                response: 422,
                description: 'OTP không hợp lệ hoặc đã hết hạn (A3/A4)',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/ValidationErrorResponse'
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Lỗi server',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/ServerErrorResponse'
                )
            )
        ]
    )]
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        try {
            $serviceReturn = $this->profileService->verifyAndUpdateSensitiveFields(
                $request->user(),
                $request->input('otp'),
                $request->input('sensitive_data')
            );

            return $this->sendSuccess(
                data: (new ProfileResource($serviceReturn->getData()))->toArray($request),
                message: 'Xác thực và cập nhật thông tin thành công.'
            );
        } catch (ServiceException $e) {
            return $this->sendError($e->getMessage(), $e->getCode());
        }
    }

    /**
     * UC-05: Change Password
     * Thay đổi mật khẩu của người dùng. Yêu cầu nhập mật khẩu hiện tại để xác thực.
     * Mật khẩu mới phải có ít nhất 8 ký tự, bao gồm cả chữ và số.
     */
    #[OA\Post(
        path: '/api/v1/user/profile/change-password',
        description: 'Thay đổi mật khẩu của người dùng. Yêu cầu nhập mật khẩu hiện tại để xác thực.',
        summary: 'UC-05: Thay đổi mật khẩu',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: '#/components/schemas/ChangePasswordRequest'
            )
        ),
        tags: ['User Profile'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Thành công - Đổi mật khẩu thành công',
                content: new OA\JsonContent(
                    required: ['message'],
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Đổi mật khẩu thành công.')
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Tài khoản bị khóa',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/ForbiddenResponse'
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Mật khẩu mới không hợp lệ (A3/A4)',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/ValidationErrorResponse'
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Lỗi server',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/ServerErrorResponse'
                )
            )
        ]
    )]
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        try {
            // Check if new password is same as current password
            $user = $request->user();
            if (Hash::check($request->input('new_password'), $user->password)) {
                return $this->sendError(
                    message: 'Mật khẩu mới không được trùng mật khẩu cũ',
                );
            }

            $this->profileService->changePassword(
                $user,
                $request->input('new_password'),
            );

            return $this->sendSuccess(
                message: 'Đổi mật khẩu thành công'
            );
        } catch (ServiceException $e) {
            return $this->sendError(
                message: $e->getMessage(),
                code: $e->getCode(),
            );
        }
    }
}
