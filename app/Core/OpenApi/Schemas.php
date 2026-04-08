<?php

declare(strict_types=1);

namespace App\Core\OpenApi;

use OpenApi\Attributes as OA;

/**
 * OpenAPI Schema Components
 *
 * Định nghĩa các schema dùng chung cho API documentation.
 */
class Schemas
{
    /**
     * Profile Response Schema
     * Schema cho response thông tin hồ sơ người dùng
     */
    #[OA\Schema(
        schema: 'ProfileResponse',
        description: 'Thông tin hồ sơ người dùng',
        required: ['id', 'role', 'phone', 'is_verified', 'is_phone_verified'],
        properties: [
            new OA\Property(property: 'id', type: 'integer', example: 1, description: 'ID người dùng'),
            new OA\Property(property: 'role', type: 'integer', enum: [1, 2, 3], example: 2, description: 'Vai trò: 1=Driver, 2=Customer, 3=Merchant'),
            new OA\Property(property: 'role_label', type: 'string', example: 'Khách hàng', description: 'Tên vai trò'),
            new OA\Property(
                property: 'avatar',
                description: 'Ảnh đại diện',
                properties: [
                    new OA\Property(property: 'value', type: 'string', nullable: true, example: 'https://example.com/avatar.jpg'),
                    new OA\Property(property: 'display', type: 'string', example: 'Chưa cập nhật'),
                    new OA\Property(property: 'field', type: 'string', example: 'avatar')
                ],
                type: 'object'
            ),
            new OA\Property(
                property: 'full_name',
                properties: [
                    new OA\Property(property: 'value', type: 'string', nullable: true, example: 'Nguyễn Văn A'),
                    new OA\Property(property: 'display', type: 'string', example: 'Nguyễn Văn A'),
                    new OA\Property(property: 'field', type: 'string', example: 'full_name')
                ],
                type: 'object'
            ),
            new OA\Property(property: 'phone', type: 'string', example: '0912345678', description: 'Số điện thoại'),
            new OA\Property(
                property: 'email',
                properties: [
                    new OA\Property(property: 'value', type: 'string', nullable: true, example: 'user@example.com'),
                    new OA\Property(property: 'display', type: 'string', example: 'Chưa cập nhật'),
                    new OA\Property(property: 'field', type: 'string', example: 'email')
                ],
                type: 'object'
            ),
            new OA\Property(
                property: 'gender',
                properties: [
                    new OA\Property(property: 'value', type: 'integer', nullable: true, enum: [1, 2, 3], example: 1),
                    new OA\Property(property: 'display', type: 'string', example: 'Nam'),
                    new OA\Property(property: 'field', type: 'string', example: 'gender')
                ],
                type: 'object'
            ),
            new OA\Property(property: 'gender_label', type: 'string', nullable: true, example: 'Nam'),
            new OA\Property(
                property: 'address',
                properties: [
                    new OA\Property(property: 'value', type: 'string', nullable: true, example: '123 Đường ABC, Quận 1, TP.HCM'),
                    new OA\Property(property: 'display', type: 'string', example: 'Chưa cập nhật'),
                    new OA\Property(property: 'field', type: 'string', example: 'address')
                ],
                type: 'object'
            ),
            new OA\Property(
                property: 'citizen_id',
                properties: [
                    new OA\Property(property: 'value', type: 'string', nullable: true, example: '123456789012'),
                    new OA\Property(property: 'display', type: 'string', example: 'Chưa cập nhật'),
                    new OA\Property(property: 'field', type: 'string', example: 'citizen_id')
                ],
                type: 'object'
            ),
            new OA\Property(property: 'is_verified', type: 'boolean', example: true, description: 'Đã xác thực tài khoản'),
            new OA\Property(property: 'is_phone_verified', type: 'boolean', example: true, description: 'Đã xác thực số điện thoại'),
            new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true, example: '2024-01-15T10:30:00Z'),
            new OA\Property(
                property: 'customer_specific',
                description: 'Thông tin riêng cho Customer',
                properties: [
                    new OA\Property(property: 'birthday', type: 'string', nullable: true, example: '1990-01-15')
                ],
                type: 'object',
                nullable: true
            ),
            new OA\Property(
                property: 'driver_specific',
                description: 'Thông tin riêng cho Driver',
                type: 'object',
                nullable: true
            ),
            new OA\Property(
                property: 'merchant_specific',
                description: 'Thông tin riêng cho Merchant',
                type: 'object',
                nullable: true
            )
        ]
    )]
    public static function profileResponse(): void
    {
        // Schema definition only
    }

    /**
     * Saved Address Response Schema
     * Schema cho response địa chỉ đã lưu
     */
    #[OA\Schema(
        schema: 'SavedAddressResponse',
        description: 'Thông tin địa chỉ đã lưu',
        required: ['id', 'label', 'name', 'address_text', 'location', 'receiver_name', 'receiver_phone', 'is_default'],
        properties: [
            new OA\Property(property: 'id', type: 'integer', example: 1, description: 'ID địa chỉ'),
            new OA\Property(property: 'label', type: 'integer', enum: [1, 2, 3, 4], example: 1, description: 'Nhãn: 1=Nhà, 2=Công ty, 3=Nhà hàng yêu thích, 4=Khác'),
            new OA\Property(property: 'label_text', type: 'string', example: 'Nhà', description: 'Tên nhãn'),
            new OA\Property(property: 'name', type: 'string', maxLength: 200, example: 'Nhà A', description: 'Tên gợi nhớ'),
            new OA\Property(property: 'address_text', type: 'string', maxLength: 500, example: '123 Đường ABC, Phường 5, Quận 1, TP.HCM', description: 'Địa chỉ đầy đủ'),
            new OA\Property(
                property: 'location',
                description: 'Tọa độ địa lý',
                properties: [
                    new OA\Property(property: 'latitude', type: 'number', format: 'double', example: 10.7629),
                    new OA\Property(property: 'longitude', type: 'number', format: 'double', example: 106.6818)
                ],
                type: 'object'
            ),
            new OA\Property(property: 'receiver_name', type: 'string', maxLength: 100, example: 'Nguyễn Văn A', description: 'Tên người nhận'),
            new OA\Property(property: 'receiver_phone', type: 'string', maxLength: 20, example: '0912345678', description: 'SĐT người nhận'),
            new OA\Property(property: 'note', type: 'string', nullable: true, maxLength: 500, example: 'Gần siêu thị', description: 'Ghi chú'),
            new OA\Property(property: 'is_default', type: 'boolean', example: true, description: 'Là địa chỉ mặc định'),
            new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2024-01-15T10:30:00Z'),
            new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2024-01-15T10:30:00Z')
        ]
    )]
    public static function savedAddressResponse(): void
    {
        // Schema definition only
    }

    /**
     * OTP Required Response Schema
     * Schema cho response yêu cầu xác thực OTP
     */
    #[OA\Schema(
        schema: 'OtpRequiredResponse',
        description: 'Response yêu cầu xác thực OTP',
        required: ['success', 'data', 'message'],
        properties: [
            new OA\Property(property: 'success', type: 'boolean', example: true),
            new OA\Property(
                property: 'data',
                properties: [
                    new OA\Property(property: 'requires_otp', type: 'boolean', example: true),
                    new OA\Property(property: 'sensitive_fields', type: 'array', items: new OA\Items(type: 'string'), example: ['phone', 'email'])
                ],
                type: 'object'
            ),
            new OA\Property(property: 'message', type: 'string', example: 'Có thay đổi thông tin nhạy cảm. Vui lòng xác thực OTP.')
        ]
    )]
    public static function otpRequiredResponse(): void
    {
        // Schema definition only
    }

    /**
     * Error Response Schema
     * Schema cho response lỗi
     */
    #[OA\Schema(
        schema: 'ErrorResponse',
        description: 'Response lỗi',
        required: ['success', 'message'],
        properties: [
            new OA\Property(property: 'success', type: 'boolean', example: false),
            new OA\Property(property: 'message', type: 'string', example: 'Có lỗi xảy ra.'),
            new OA\Property(
                property: 'errors',
                description: 'Danh sách lỗi validation',
                type: 'object',
                nullable: true,
                additionalProperties: new OA\AdditionalProperties(type: 'array', items: new OA\Items(type: 'string'))
            )
        ]
    )]
    public static function errorResponse(): void
    {
        // Schema definition only
    }

    /**
     * Forbidden Response Schema
     */
    #[OA\Schema(
        schema: 'ForbiddenResponse',
        description: 'Lỗi không có quyền truy cập hoặc tài khoản bị khóa',
        required: ['success', 'message'],
        properties: [
            new OA\Property(property: 'success', type: 'boolean', example: false),
            new OA\Property(property: 'message', type: 'string', example: 'Tài khoản của bạn đã bị khóa hoặc bạn không có quyền thực hiện hành động này.')
        ]
    )]
    public static function forbiddenResponse(): void {}

    /**
     * Validation Error Response Schema
     */
    #[OA\Schema(
        schema: 'ValidationErrorResponse',
        description: 'Lỗi dữ liệu đầu vào không hợp lệ',
        required: ['success', 'message', 'errors'],
        properties: [
            new OA\Property(property: 'success', type: 'boolean', example: false),
            new OA\Property(property: 'message', type: 'string', example: 'Dữ liệu không hợp lệ.'),
            new OA\Property(
                property: 'errors',
                type: 'object',
                additionalProperties: new OA\AdditionalProperties(type: 'array', items: new OA\Items(type: 'string'))
            )
        ]
    )]
    public static function validationErrorResponse(): void {}

    /**
     * Server Error Response Schema
     */
    #[OA\Schema(
        schema: 'ServerErrorResponse',
        description: 'Lỗi server nội bộ',
        required: ['success', 'message'],
        properties: [
            new OA\Property(property: 'success', type: 'boolean', example: false),
            new OA\Property(property: 'message', type: 'string', example: 'Có lỗi xảy ra trên hệ thống. Vui lòng thử lại sau.')
        ]
    )]
    public static function serverErrorResponse(): void {}

    /**
     * Unauthorized Response Schema
     */
    #[OA\Schema(
        schema: 'UnauthorizedResponse',
        description: 'Lỗi chưa đăng nhập hoặc token hết hạn',
        required: ['success', 'message'],
        properties: [
            new OA\Property(property: 'success', type: 'boolean', example: false),
            new OA\Property(property: 'message', type: 'string', example: 'Vui lòng đăng nhập để thực hiện hành động này.')
        ]
    )]
    public static function unauthorizedResponse(): void {}

    /**
     * Verify OTP Profile Request Schema
     */
    #[OA\Schema(
        schema: 'VerifyOtpProfileRequest',
        description: 'Request body để xác thực OTP khi thay đổi thông tin nhạy cảm',
        required: ['otp'],
        properties: [
            new OA\Property(property: 'otp', type: 'string', example: '123456', description: 'Mã OTP gồm 6 chữ số'),
            new OA\Property(
                property: 'sensitive_data',
                description: 'Dữ liệu nhạy cảm cần cập nhật (ví dụ: phone hoặc email)',
                type: 'object',
                nullable: true
            )
        ]
    )]
    public static function verifyOtpProfileRequest(): void {}

    /**
     * Change Password Request Schema
     */
    #[OA\Schema(
        schema: 'ChangePasswordRequest',
        description: 'Request body để thay đổi mật khẩu',
        required: ['current_password', 'new_password', 'new_password_confirmation'],
        properties: [
            new OA\Property(property: 'current_password', type: 'string', example: 'OldPassword123!', description: 'Mật khẩu hiện tại'),
            new OA\Property(property: 'new_password', type: 'string', example: 'NewPassword123!', description: 'Mật khẩu mới (tối thiểu 8 ký tự, bao gồm chữ và số)'),
            new OA\Property(property: 'new_password_confirmation', type: 'string', example: 'NewPassword123!', description: 'Xác nhận mật khẩu mới')
        ]
    )]
    public static function changePasswordRequest(): void {}
}
