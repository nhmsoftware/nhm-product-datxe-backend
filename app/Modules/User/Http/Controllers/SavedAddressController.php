<?php

declare(strict_types=1);

namespace App\Modules\User\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Core\Services\ServiceException;
use App\Modules\User\Http\Requests\SaveAddressRequest;
use App\Modules\User\Http\Requests\UpdateAddressRequest;
use App\Modules\User\Interfaces\SavedAddressServiceInterface;
use App\Modules\User\Model\CustomerSavedAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class SavedAddressController extends BaseController
{
    public function __construct(
        private readonly SavedAddressServiceInterface $savedAddressService
    ) {}

    /**
     * UC-06: List all saved addresses
     * Lấy danh sách tất cả địa chỉ đã lưu của khách hàng.
     */
    #[OA\Get(
        path: '/api/v1/user/addresses',
        description: "Trả về danh sách tất cả địa chỉ đã lưu của khách hàng hiện tại. Chỉ áp dụng cho vai trò Customer. Mỗi khách hàng có thể lưu tối đa 10 địa chỉ.\n\n**Preconditions:** Người dùng đã đăng nhập với vai trò Customer. Tài khoản đang hoạt động.\n\n**Postconditions:** Trả về danh sách các địa chỉ đã lưu của khách hàng, sắp xếp theo thứ tự địa chỉ mặc định trước.",
        summary: 'UC-06: Lấy danh sách địa chỉ đã lưu',
        security: [['sanctum' => []]],
        tags: ['Saved Addresses'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Thành công - Trả về danh sách địa chỉ',
                content: new OA\JsonContent(
                    required: ['success', 'data', 'message'],
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/SavedAddressResponse')
                        ),
                        new OA\Property(property: 'message', type: 'string', example: 'Lấy danh sách địa chỉ thành công.')
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Không có quyền - Người dùng không phải là Customer',
                content: new OA\JsonContent(
                    required: ['success', 'message'],
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Chỉ khách hàng mới có thể sử dụng chức năng này.')
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Không tìm thấy thông tin khách hàng',
                content: new OA\JsonContent(
                    required: ['success', 'message'],
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Không tìm thấy thông tin khách hàng.')
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
                        new OA\Property(property: 'message', type: 'string', example: 'Không thể tải danh sách địa chỉ. Vui lòng thử lại.')
                    ]
                )
            )
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        try {
            $serviceReturn = $this->savedAddressService->getAddresses($request->user());

            return $this->sendSuccess(
                data: $serviceReturn->getData(),
                message: 'Lấy danh sách địa chỉ thành công.'
            );
        } catch (ServiceException $e) {
            return $this->sendError($e->getMessage(), $e->getCode());
        }
    }

    /**
     * UC-06: Show a specific saved address
     * Xem chi tiết một địa chỉ đã lưu theo ID.
     */
    #[OA\Get(
        path: '/api/v1/user/addresses/{id}',
        description: "Trả về thông tin chi tiết của một địa chỉ đã lưu theo ID. Người dùng chỉ có thể xem địa chỉ thuộc về tài khoản của mình.\n\n**Preconditions:** Người dùng đã đăng nhập với vai trò Customer. Địa chỉ tồn tại và thuộc về tài khoản người dùng.\n\n**Postconditions:** Trả về thông tin chi tiết của địa chỉ được yêu cầu.",
        summary: 'UC-06: Xem chi tiết địa chỉ đã lưu',
        security: [['sanctum' => []]],
        tags: ['Saved Addresses'],
        parameters: [
            new OA\PathParameter(name: 'id', description: 'ID của địa chỉ', required: true, schema: new OA\Schema(type: 'integer', example: 1))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Thành công - Trả về thông tin địa chỉ',
                content: new OA\JsonContent(
                    required: ['success', 'data', 'message'],
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', ref: '#/components/schemas/SavedAddressResponse'),
                        new OA\Property(property: 'message', type: 'string', example: 'Lấy thông tin địa chỉ thành công.')
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Không có quyền xem địa chỉ này',
                content: new OA\JsonContent(
                    required: ['success', 'message'],
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Bạn không có quyền xem địa chỉ này.')
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Không tìm thấy địa chỉ',
                content: new OA\JsonContent(
                    required: ['success', 'message'],
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Không tìm thấy địa chỉ.')
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
                        new OA\Property(property: 'message', type: 'string', example: 'Không thể tải thông tin địa chỉ. Vui lòng thử lại.')
                    ]
                )
            )
        ]
    )]
    public function show(int $id, Request $request): JsonResponse
    {
        try {
            $serviceReturn = $this->savedAddressService->getAddress($request->user(), $id);

            return $this->sendSuccess(
                data: $serviceReturn->getData(),
                message: 'Lấy thông tin địa chỉ thành công.'
            );
        } catch (ServiceException $e) {
            return $this->sendError($e->getMessage(), $e->getCode());
        }
    }

    /**
     * UC-06: Create a new saved address
     * Tạo mới một địa chỉ đã lưu cho khách hàng.
     */
    #[OA\Post(
        path: '/api/v1/user/addresses',
        description: "Tạo mới một địa chỉ đã lưu cho khách hàng. Nếu không cung cấp tên người nhận, hệ thống sẽ tự động lấy từ thông tin tài khoản. Mỗi khách hàng có thể lưu tối đa 10 địa chỉ.\n\n**Preconditions:** Người dùng đã đăng nhập với vai trò Customer. Chưa đạt giới hạn 10 địa chỉ. Tài khoản đang hoạt động.\n\n**Postconditions:** Địa chỉ mới được tạo và lưu vào danh sách. Nếu is_default=true, địa chỉ này sẽ được đặt làm mặc định.\n\n**Alternative Flows:**\nA2: Địa chỉ đã tồn tại → Trả về thông báo và ID địa chỉ đã tồn tại. A4: Đạt giới hạn 10 địa chỉ → Yêu cầu xóa bớt trước.",
        summary: 'UC-06: Tạo mới địa chỉ đã lưu',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['label', 'name', 'address_text', 'location'],
                properties: [
                    new OA\Property(property: 'label', description: 'Nhãn địa chỉ: 1=Nhà, 2=Công ty, 3=Nhà hàng yêu thích, 4=Khác', type: 'integer', example: 1, enum: [1, 2, 3, 4]),
                    new OA\Property(property: 'name', description: 'Tên gợi nhớ cho địa chỉ', type: 'string', example: 'Nhà A', maxLength: 200),
                    new OA\Property(property: 'address_text', description: 'Địa chỉ đầy đủ dạng text', type: 'string', example: '123 Đường ABC, Phường 5, Quận 1, TP.HCM', maxLength: 500),
                    new OA\Property(
                        property: 'location',
                        description: 'Tọa độ địa lý',
                        properties: [
                            new OA\Property(property: 'lat', type: 'number', format: 'double', example: 10.7629, description: 'Vĩ độ'),
                            new OA\Property(property: 'lng', type: 'number', format: 'double', example: 106.6818, description: 'Kinh độ')
                        ],
                        type: 'object'
                    ),
                    new OA\Property(property: 'receiver_name', type: 'string', maxLength: 100, example: 'Nguyễn Văn A', description: 'Tên người nhận (mặc định: full_name của user)'),
                    new OA\Property(property: 'receiver_phone', type: 'string', maxLength: 20, example: '0912345678', description: 'Số điện thoại người nhận (mặc định: phone của user)'),
                    new OA\Property(property: 'note', type: 'string', maxLength: 500, example: 'Gần siêu thị', description: 'Ghi chú thêm cho tài xế'),
                    new OA\Property(property: 'is_default', type: 'boolean', example: false, description: 'Đặt làm địa chỉ mặc định')
                ]
            )
        ),
        tags: ['Saved Addresses'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Thành công - Tạo địa chỉ thành công',
                content: new OA\JsonContent(
                    required: ['success', 'data', 'message'],
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', ref: '#/components/schemas/SavedAddressResponse'),
                        new OA\Property(property: 'message', type: 'string', example: 'Địa chỉ đã được lưu thành công.')
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Không có quyền - Người dùng không phải là Customer',
                content: new OA\JsonContent(
                    required: ['success', 'message'],
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Chỉ khách hàng mới có thể sử dụng chức năng này.')
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Không tìm thấy thông tin khách hàng',
                content: new OA\JsonContent(
                    required: ['success', 'message'],
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Không tìm thấy thông tin khách hàng.')
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Dữ liệu không hợp lệ - Địa chỉ đã tồn tại (A2) hoặc đã đạt giới hạn tối đa (A4).',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/ErrorResponse'
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Lỗi server',
                content: new OA\JsonContent(
                    required: ['success', 'message'],
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Không thể lưu địa chỉ lúc này. Vui lòng thử lại sau.')
                    ]
                )
            )
        ]
    )]
    public function store(SaveAddressRequest $request): JsonResponse
    {
        $serviceReturn = $this->savedAddressService->createAddress(
            $request->user(),
            $request->validated()
        );

        if (!$serviceReturn->isSuccess()) {
            return $this->sendError($serviceReturn->getMessage(), $serviceReturn->getCode() ?: 400);
        }

        return $this->sendSuccess(
            data: $serviceReturn->getData(),
            message: 'Địa chỉ đã được lưu thành công.'
        );
    }

    /**
     * UC-06: Update a saved address
     * Cập nhật thông tin một địa chỉ đã lưu.
     */
    #[OA\Put(
        path: '/api/v1/user/addresses/{id}',
        description: "Cập nhật thông tin của một địa chỉ đã lưu. Người dùng chỉ có thể cập nhật địa chỉ thuộc về tài khoản của mình.\n\n**Preconditions:** Người dùng đã đăng nhập với vai trò Customer. Địa chỉ tồn tại và thuộc về tài khoản người dùng.\n\n**Postconditions:** Thông tin địa chỉ được cập nhật thành công với dữ liệu mới.",
        summary: 'UC-06: Cập nhật địa chỉ đã lưu',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['label', 'name', 'address_text', 'location'],
                properties: [
                    new OA\Property(property: 'label', type: 'integer', enum: [1, 2, 3, 4], example: 1, description: 'Nhãn địa chỉ: 1=Nhà, 2=Công ty, 3=Nhà hàng yêu thích, 4=Khác'),
                    new OA\Property(property: 'name', type: 'string', maxLength: 200, example: 'Nhà B', description: 'Tên gợi nhớ cho địa chỉ'),
                    new OA\Property(property: 'address_text', type: 'string', maxLength: 500, example: '456 Đường XYZ, Phường 3, Quận 2, TP.HCM', description: 'Địa chỉ đầy đủ dạng text'),
                    new OA\Property(
                        property: 'location',
                        description: 'Tọa độ địa lý',
                        properties: [
                            new OA\Property(property: 'latitude', type: 'number', format: 'double', example: 10.7890, description: 'Vĩ độ'),
                            new OA\Property(property: 'longitude', type: 'number', format: 'double', example: 106.7000, description: 'Kinh độ')
                        ],
                        type: 'object'
                    ),
                    new OA\Property(property: 'receiver_name', type: 'string', maxLength: 100, example: 'Nguyễn Văn B', description: 'Tên người nhận'),
                    new OA\Property(property: 'receiver_phone', type: 'string', maxLength: 20, example: '0987654321', description: 'Số điện thoại người nhận'),
                    new OA\Property(property: 'note', type: 'string', maxLength: 500, example: 'Gần trường học', description: 'Ghi chú thêm cho tài xế')
                ]
            )
        ),
        tags: ['Saved Addresses'],
        parameters: [
            new OA\PathParameter(name: 'id', description: 'ID của địa chỉ cần cập nhật', required: true, schema: new OA\Schema(type: 'integer', example: 1))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Thành công - Cập nhật địa chỉ thành công',
                content: new OA\JsonContent(
                    required: ['success', 'data', 'message'],
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', ref: '#/components/schemas/SavedAddressResponse'),
                        new OA\Property(property: 'message', type: 'string', example: 'Cập nhật địa chỉ thành công.')
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Không có quyền cập nhật địa chỉ này',
                content: new OA\JsonContent(
                    required: ['success', 'message'],
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Bạn không có quyền chỉnh sửa địa chỉ này.')
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Không tìm thấy địa chỉ',
                content: new OA\JsonContent(
                    required: ['success', 'message'],
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Không tìm thấy địa chỉ.')
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error - Dữ liệu không hợp lệ',
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
                        new OA\Property(property: 'message', type: 'string', example: 'Không thể cập nhật địa chỉ lúc này. Vui lòng thử lại sau.')
                    ]
                )
            )
        ]
    )]
    public function update(int $id, UpdateAddressRequest $request): JsonResponse
    {
        try {
            $serviceReturn = $this->savedAddressService->updateAddress(
                $request->user(),
                $id,
                $request->validated()
            );

            return $this->sendSuccess(
                data: $serviceReturn->getData(),
                message: 'Cập nhật địa chỉ thành công.'
            );
        } catch (ServiceException $e) {
            return $this->sendError($e->getMessage(), $e->getCode());
        }
    }

    /**
     * UC-06: Delete a saved address
     * Xóa một địa chỉ đã lưu khỏi danh sách.
     */
    #[OA\Delete(
        path: '/api/v1/user/addresses/{id}',
        description: "Xóa một địa chỉ đã lưu khỏi danh sách của khách hàng. Người dùng chỉ có thể xóa địa chỉ thuộc về tài khoản của mình. Nếu xóa địa chỉ mặc định, hệ thống sẽ tự động chọn địa chỉ khác làm mặc định (nếu có).\n\n**Preconditions:** Người dùng đã đăng nhập với vai trò Customer. Địa chỉ tồn tại và thuộc về tài khoản người dùng.\n\n**Postconditions:** Địa chỉ được xóa khỏi danh sách. Nếu là địa chỉ mặc định, địa chỉ đầu tiên còn lại sẽ được chọn làm mặc định.",
        summary: 'UC-06: Xóa địa chỉ đã lưu',
        security: [['sanctum' => []]],
        tags: ['Saved Addresses'],
        parameters: [
            new OA\PathParameter(name: 'id', description: 'ID của địa chỉ cần xóa', required: true, schema: new OA\Schema(type: 'integer', example: 1))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Thành công - Xóa địa chỉ thành công',
                content: new OA\JsonContent(
                    required: ['success', 'message'],
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Xóa địa chỉ thành công.')
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Không có quyền xóa địa chỉ này',
                content: new OA\JsonContent(
                    required: ['success', 'message'],
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Bạn không có quyền xóa địa chỉ này.')
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Không tìm thấy địa chỉ',
                content: new OA\JsonContent(
                    required: ['success', 'message'],
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Không tìm thấy địa chỉ.')
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
                        new OA\Property(property: 'message', type: 'string', example: 'Không thể xóa địa chỉ lúc này. Vui lòng thử lại sau.')
                    ]
                )
            )
        ]
    )]
    public function destroy(int $id, Request $request): JsonResponse
    {
        try {
            $this->savedAddressService->deleteAddress($request->user(), $id);

            return $this->sendSuccess(
                message: 'Xóa địa chỉ thành công.'
            );
        } catch (ServiceException $e) {
            return $this->sendError($e->getMessage(), $e->getCode());
        }
    }

    /**
     * UC-06: Set address as default
     * Đặt một địa chỉ làm địa chỉ mặc định.
     */
    #[OA\Post(
        path: '/api/v1/user/addresses/{id}/default',
        description: "Đặt một địa chỉ đã lưu làm địa chỉ mặc định cho việc giao hàng. Địa chỉ mặc định sẽ được tự động chọn khi tạo đơn hàng mới. Chỉ có thể đặt địa chỉ thuộc về tài khoản người dùng.\n\n**Preconditions:** Người dùng đã đăng nhập với vai trò Customer. Địa chỉ tồn tại và thuộc về tài khoản người dùng.\n\n**Postconditions:** Địa chỉ được chọn sẽ trở thành địa chỉ mặc định. Địa chỉ mặc định trước đó sẽ bị hủy.",
        summary: 'UC-06: Đặt địa chỉ mặc định',
        security: [['sanctum' => []]],
        tags: ['Saved Addresses'],
        parameters: [
            new OA\PathParameter(name: 'id', description: 'ID của địa chỉ cần đặt làm mặc định', required: true, schema: new OA\Schema(type: 'integer', example: 1))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Thành công - Đặt địa chỉ mặc định thành công',
                content: new OA\JsonContent(
                    required: ['success', 'data', 'message'],
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', ref: '#/components/schemas/SavedAddressResponse'),
                        new OA\Property(property: 'message', type: 'string', example: 'Đặt địa chỉ mặc định thành công.')
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Không có quyền cập nhật địa chỉ này',
                content: new OA\JsonContent(
                    required: ['success', 'message'],
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Bạn không có quyền cập nhật địa chỉ này.')
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Không tìm thấy địa chỉ',
                content: new OA\JsonContent(
                    required: ['success', 'message'],
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Không tìm thấy địa chỉ.')
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
                        new OA\Property(property: 'message', type: 'string', example: 'Không thể cập nhật địa chỉ lúc này. Vui lòng thử lại sau.')
                    ]
                )
            )
        ]
    )]
    public function setDefault(int $id, Request $request): JsonResponse
    {
        try {
            $serviceReturn = $this->savedAddressService->setAsDefault($request->user(), $id);

            return $this->sendSuccess(
                data: $serviceReturn->getData(),
                message: 'Đặt địa chỉ mặc định thành công.'
            );
        } catch (ServiceException $e) {
            return $this->sendError($e->getMessage(), $e->getCode());
        }
    }
}
