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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class EditProfileController extends BaseController
{
    public function __construct(
        private readonly ProfileServiceInterface $profileService
    ) {}

    /**
     * UC-05: Edit Profile - Show edit form
     * Trả về thông tin profile hiện tại để user có thể edit.
     */
    #[OA\Get(
        path: '/api/v1/user/profile/edit',
        description: "Trả về thông tin hồ sơ hiện tại của người dùng để hiển thị form chỉnh sửa. Bao gồm tất cả các trường có thể chỉnh sửa với giá trị hiện tại.\n\n**Preconditions:** Người dùng đã đăng nhập thành công. Tài khoản đang ở trạng thái hoạt động.\n\n**Postconditions:** Trả về đầy đủ thông tin hồ sơ với các trường có thể chỉnh sửa. Người dùng có thể thực hiện chỉnh sửa thông tin (UC-05 Update).",
        summary: 'UC-05: Lấy thông tin hồ sơ để chỉnh sửa',
        security: [['sanctum' => []]],
        tags: ['User Profile'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Thành công - Trả về thông tin hồ sơ để chỉnh sửa',
                content: new OA\JsonContent(
                    required: ['success', 'data', 'message'],
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'object', ref: '#/components/schemas/ProfileResponse'),
                        new OA\Property(property: 'message', type: 'string', example: 'Lấy thông tin chỉnh sửa thành công.')
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Tài khoản bị khóa',
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
                description: 'Lỗi server',
                content: new OA\JsonContent(
                    required: ['success', 'message'],
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Không thể tải thông tin. Vui lòng thử lại.')
                    ]
                )
            )
        ]
    )]
    public function edit(Request $request): JsonResponse
    {
        try {
            $serviceReturn = $this->profileService->getProfile($request->user());

            return $this->sendSuccess(
                data: (new ProfileResource($serviceReturn->getData()))->toArray($request),
                message: 'Lấy thông tin chỉnh sửa thành công.'
            );
        } catch (ServiceException $e) {
            return $this->sendError($e->getMessage(), $e->getCode());
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
            content: new OA\JsonContent(
                required: ['full_name', 'gender', 'address', 'avatar', 'citizen_id', 'birthday', 'vehicle_name', 'vehicle_type', 'vehicle_color', 'vehicle_number', 'license_number', 'bank_name', 'bank_account_number', 'bank_account_holder', 'store_name', 'store_address', 'opening_time', 'closing_time', 'is_open'],
                properties: [
                    // Common fields
                    new OA\Property(property: 'full_name', description: 'Họ và tên đầy đủ', type: 'string', example: 'Nguyễn Văn B', maxLength: 100),
                    new OA\Property(property: 'gender', description: 'Giới tính: 1=Nam, 2=Nữ, 3=Khác', type: 'integer', example: 1, enum: [1, 2, 3]),
                    new OA\Property(property: 'address', type: 'string', maxLength: 500, example: '123 Đường ABC, Quận 1, TP.HCM', description: 'Địa chỉ liên hệ'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255, example: 'user@example.com', description: 'Email (thay đổi sẽ yêu cầu OTP)'),
                    new OA\Property(property: 'phone', type: 'string', maxLength: 20, example: '0912345678', description: 'Số điện thoại (thay đổi sẽ yêu cầu OTP)'),
                    new OA\Property(property: 'citizen_id', type: 'string', pattern: '^[0-9]{12}$', maxLength: 20, example: '123456789012', description: 'Số CCCD/CMND'),
                    new OA\Property(property: 'avatar', type: 'string', maxLength: 500, example: 'https://example.com/avatar.jpg', description: 'URL ảnh đại diện'),
                    new OA\Property(property: 'birthday', type: 'string', format: 'date', example: '1990-01-15', description: 'Ngày sinh (format: YYYY-MM-DD)'),
                    // Driver-specific fields
                    new OA\Property(property: 'vehicle_name', type: 'string', maxLength: 100, example: 'Honda Wave', description: 'Tên phương tiện (Driver only)'),
                    new OA\Property(property: 'vehicle_type', type: 'integer', example: 1, description: 'Loại phương tiện: 1=Xe máy, 2=Ô tô (Driver only)'),
                    new OA\Property(property: 'vehicle_color', type: 'integer', example: 1, description: 'Màu phương tiện (Driver only)'),
                    new OA\Property(property: 'vehicle_number', type: 'string', maxLength: 20, example: '59A-123.45', description: 'Biển số xe (Driver only)'),
                    new OA\Property(property: 'license_number', type: 'string', maxLength: 50, example: '0123456789', description: 'Số GPLX (Driver only)'),
                    new OA\Property(property: 'bank_name', type: 'string', maxLength: 100, example: 'Vietcombank', description: 'Tên ngân hàng (Driver only)'),
                    new OA\Property(property: 'bank_account_number', type: 'string', maxLength: 50, example: '1234567890', description: 'Số tài khoản ngân hàng (Driver only)'),
                    new OA\Property(property: 'bank_account_holder', description: 'Tên chủ tài khoản (Driver only)', type: 'string', example: 'NGUYEN VAN B', maxLength: 100),
                    // Merchant-specific fields
                    new OA\Property(property: 'store_name', type: 'string', maxLength: 200, example: 'Quán Cơm Ngon', description: 'Tên cửa hàng (Merchant only)'),
                    new OA\Property(property: 'store_address', type: 'string', maxLength: 500, example: '456 Đường XYZ, Quận 2, TP.HCM', description: 'Địa chỉ cửa hàng (Merchant only)'),
                    new OA\Property(property: 'opening_time', type: 'string', pattern: '^([01]?[0-9]|2[0-3]):[0-5][0-9]$', example: '08:00', description: 'Giờ mở cửa (Merchant only)'),
                    new OA\Property(property: 'closing_time', type: 'string', pattern: '^([01]?[0-9]|2[0-3]):[0-5][0-9]$', example: '22:00', description: 'Giờ đóng cửa (Merchant only)'),
                    new OA\Property(property: 'is_open', type: 'boolean', example: true, description: 'Trạng thái mở cửa (Merchant only)')
                ]
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
                    required: ['success', 'message'],
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ hỗ trợ.')
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error (A5) - Dữ liệu không hợp lệ',
                content: new OA\JsonContent(
                    required: ['success', 'message', 'errors'],
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Dữ liệu không hợp lệ.'),
                        new OA\Property(property: 'errors', type: 'object', additionalProperties: new OA\AdditionalProperties(type: 'array', items: new OA\Items(type: 'string')))
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Lỗi server (A7) - Không thể cập nhật thông tin',
                content: new OA\JsonContent(
                    required: ['success', 'message'],
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Không thể cập nhật thông tin lúc này. Vui lòng thử lại sau.')
                    ]
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
                required: ['otp', 'sensitive_data'],
                properties: [
                    new OA\Property(property: 'otp', type: 'string', minLength: 6, maxLength: 6, example: '123456', description: 'Mã OTP 6 số được gửi qua SMS/email'),
                    new OA\Property(
                        property: 'sensitive_data',
                        description: 'Thông tin nhạy cảm cần cập nhật',
                        properties: [
                            new OA\Property(property: 'phone', type: 'string', maxLength: 20, example: '0987654321', description: 'Số điện thoại mới'),
                            new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255, example: 'newemail@example.com', description: 'Email mới')
                        ],
                        type: 'object'
                    )
                ]
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
                    required: ['success', 'message'],
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ hỗ trợ.')
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'OTP không hợp lệ hoặc đã hết hạn (A3/A4)',
                content: new OA\JsonContent(
                    required: ['success', 'message'],
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Mã OTP không đúng. Vui lòng thử lại.')
                    ]
                )
            ),
            new OA\Response(
                response: 429,
                description: 'Quá nhiều yêu cầu - OTP đã được gửi, chờ đến khi hết hạn',
                content: new OA\JsonContent(
                    required: ['success', 'message'],
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Vui lòng chờ 60 giây trước khi gửi lại yêu cầu.')
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Lỗi server',
                content: new OA\JsonContent(
                    required: ['success', 'message'],
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Không thể cập nhật thông tin lúc này. Vui lòng thử lại sau.')
                    ]
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
        description: 'Thay đổi mật khẩu tài khoản người dùng. Yêu cầu xác thực mật khẩu hiện tại. Mật khẩu mới phải có ít nhất 8 ký tự, bao gồm chữ cái và số.',
        summary: 'UC-05: Thay đổi mật khẩu',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['current_password', 'new_password', 'new_password_confirmation'],
                properties: [
                    new OA\Property(property: 'current_password', type: 'string', format: 'password', example: 'OldPassword123', description: 'Mật khẩu hiện tại'),
                    new OA\Property(property: 'new_password', type: 'string', format: 'password', minLength: 8, maxLength: 50, example: 'NewPassword456', description: 'Mật khẩu mới (ít nhất 8 ký tự, bao gồm chữ và số)'),
                    new OA\Property(property: 'new_password_confirmation', type: 'string', format: 'password', example: 'NewPassword456', description: 'Xác nhận mật khẩu mới')
                ]
            )
        ),
        tags: ['User Profile'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Thành công - Đổi mật khẩu thành công',
                content: new OA\JsonContent(
                    required: ['success', 'message'],
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Đổi mật khẩu thành công.')
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Tài khoản bị khóa',
                content: new OA\JsonContent(
                    required: ['success', 'message'],
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ hỗ trợ.')
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Dữ liệu không hợp lệ (A10/A11) - Mật khẩu hiện tại không đúng hoặc mật khẩu mới không hợp lệ.',
                content: new OA\JsonContent(
                    required: ['success', 'message', 'errors'],
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Dữ liệu không hợp lệ.'),
                        new OA\Property(property: 'errors', type: 'object', additionalProperties: new OA\AdditionalProperties(type: 'array', items: new OA\Items(type: 'string')))
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Lỗi server',
                content: new OA\JsonContent(
                    required: ['success', 'message'],
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Không thể đổi mật khẩu lúc này. Vui lòng thử lại sau.')
                    ]
                )
            )
        ]
    )]
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        try {
            $this->profileService->changePassword(
                $request->user(),
                $request->input('new_password')
            );

            return $this->sendSuccess(
                message: 'Đổi mật khẩu thành công.'
            );
        } catch (ServiceException $e) {
            return $this->sendError($e->getMessage(), $e->getCode());
        }
    }
}
