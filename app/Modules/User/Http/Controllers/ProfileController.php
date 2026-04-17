<?php

declare(strict_types=1);

namespace App\Modules\User\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\User\Http\Requests\ChangePasswordRequest;
use App\Modules\User\Http\Requests\EditProfileRequest;
use App\Modules\User\Http\Requests\VerifyOtpRequest;
use App\Modules\User\Http\Resources\ProfileResource;
use App\Modules\User\Interfaces\ProfileServiceInterface;
use App\Modules\User\DTO\UpdateProfileDTO;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class ProfileController extends BaseController
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
        $serviceReturn = $this->profileService->getProfile($request->user());

        if ($serviceReturn->isError()) {
            return $this->sendError(message: $serviceReturn->getMessage(), code: $serviceReturn->getCode());
        }

        return $this->sendSuccess(
            data: (new ProfileResource($serviceReturn->getData()))->toArray($request),
            message: 'Lấy thông tin hồ sơ thành công.'
        );
    }

    /**
     * UC-05: Update Profile
     * Cập nhật thông tin cá nhân của người dùng. Nếu có thay đổi thông tin nhạy cảm (phone, email),
     * hệ thống sẽ yêu cầu xác thực OTP trước khi cập nhật.
     */
    #[OA\Put(
        path: '/api/v1/user/profile',
        description: "Cập nhật thông tin cá nhân của người dùng. Nếu thay đổi số điện thoại hoặc email, hệ thống sẽ yêu cầu xác thực OTP. Các trường driver-specific và merchant-specific sẽ được cập nhật tương ứng với vai trò.\n\n**Preconditions:** Người dùng đã đăng nhập. Tài khoản đang hoạt động. Token hợp lệ.\n\n**Postconditions:** Nếu thành công: Thông tin hồ sơ được cập nhật, trả về profile mới. Nếu có thay đổi nhạy cảm: Yêu cầu xác thực OTP qua endpoint /verify-otp.\n\n**Alternative Flows:**\nA1: Thay đổi số điện thoại → Gửi OTP đến số mới.\nA5: Validation error → Trả về danh sách lỗi.\nA7: Lỗi server → Thông báo và cho thử lại.",
        summary: 'UC-05: Cập nhật thông tin hồ sơ',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            description: 'Các trường thông tin cần cập nhật. Chỉ cần gửi những trường muốn thay đổi.',
            required: true,
            content: new OA\JsonContent(
                properties: [
                    // --- Common Fields ---
                    new OA\Property(property: 'full_name', type: 'string', example: 'Nguyễn Văn B'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'new.email@example.com'),
                    new OA\Property(property: 'phone', type: 'string', example: '0123456789'),
                    new OA\Property(
                        property: 'gender', 
                        description: 'Giới tính. 1: Nam (Male), 2: Nữ (Female), 3: Khác (Other)', 
                        type: 'integer', 
                        example: 1
                    ),
                    new OA\Property(property: 'birthday', description: 'Ngày (YYYY-MM-DD)', type: 'string', format: 'date', example: '1995-08-15'),
                    new OA\Property(property: 'avatar', description: 'URL ảnh đại diện mới', type: 'string', example: 'https://example.com/avatar.jpg'),

                    // --- Driver-Specific Fields ---
                    new OA\Property(property: 'address', description: '(Driver) thường trú', example: '123 Đường ABC, Quận 1, TP. HCM'),
                    new OA\Property(property: 'identity_number', description: '(Driver) Số CMND/CCCD', type: 'string', example: '012345678912'),
                    new OA\Property(property: 'license_plate', description: '(Driver) Biển số xe', type: 'string', example: '59-T1 123.45'),
                    new OA\Property(
                        property: 'vehicle_type_id', 
                        description: '(Driver) ID loại xe. 1: Xe Máy, 2: Ô Tô 4 Chỗ, 3: Ô Tô 7 Chỗ, 4: Ô Tô 9 Chỗ', 
                        type: 'integer', 
                        example: 1
                    ),

                    // --- Merchant-Specific Fields ---
                    new OA\Property(property: 'store_name', description: '(Merchant) Tên cửa hàng', example: 'Cửa hàng tiện lợi XYZ'),
                    new OA\Property(property: 'store_address', description: '(Merchant) thường trú', example: '456 Đường DEF, Quận 3, TP. HCM'),
                    new OA\Property(property: 'tax_code', description: '(Merchant) Mã số thuế', type: 'string', example: '0312345678'),
                ],
                type: 'object'
            )
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
                        new OA\Property(property: 'data', ref: '#/components/schemas/ProfileResponse', type: 'object'),
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
        $serviceReturn = $this->profileService->updateProfile(
            UpdateProfileDTO::fromRequest($request)
        );

        if ($serviceReturn->isError()) {
            return $this->sendError(message: $serviceReturn->getMessage(), code: $serviceReturn->getCode());
        }

        return $this->sendSuccess(
            data: (new ProfileResource($serviceReturn->getData()))->toArray($request),
            message: $serviceReturn->getMessage() ?: 'Cập nhật thông tin thành công.',
            code: $serviceReturn->getCode() ?: 200
        );
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
                        new OA\Property(property: 'data', ref: '#/components/schemas/ProfileResponse', type: 'object'),
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
        $serviceReturn = $this->profileService->verifyAndUpdateSensitiveFields(
            $request->user(),
            $request->input('otp'),
            $request->input('sensitive_data')
        );
        if ($serviceReturn->isError()) {
            return $this->sendError(message: $serviceReturn->getMessage(), code: $serviceReturn->getCode());
        }

        return $this->sendSuccess(
            data: (new ProfileResource($serviceReturn->getData()))->toArray($request),
            message: 'Xác thực và cập nhật thông tin thành công.'
        );
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
            $user = $request->user();
            if (Hash::check($request->input('new_password'), $user->password)) {
                return $this->sendError(
                    message: 'Mật khẩu mới không được trùng mật khẩu cũ',
                );
            }

            $result = $this->profileService->changePassword(
                $user,
                $request->input('new_password'),
            );

            if ($result->isError()) {
                return $this->sendError(
                    message: $result->getMessage(),
                    code: $result->getCode(),
                );
            }

            return $this->sendSuccess(
                message: 'Đổi mật khẩu thành công'
            );

    }
}
