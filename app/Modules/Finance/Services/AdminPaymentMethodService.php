<?php

declare(strict_types=1);

namespace App\Modules\Finance\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Finance\Interfaces\AdminPaymentMethodServiceInterface;
use App\Modules\Finance\Interfaces\PaymentMethodRepositoryInterface;
use App\Modules\Finance\Interfaces\TopUpRepositoryInterface;
use App\Modules\Finance\Model\Enums\PaymentMethodType;

final class AdminPaymentMethodService extends BaseService implements AdminPaymentMethodServiceInterface
{
    public function __construct(
        private readonly PaymentMethodRepositoryInterface $paymentMethodRepository,
        private readonly TopUpRepositoryInterface $topUpRepository,
    ) {}

    /**
     * Lấy tất cả phương thức thanh toán cho Admin.
     */
    public function index(): ServiceReturn
    {
        return $this->execute(function (): array {
            $methods = $this->paymentMethodRepository->getAllForAdmin();

            return $methods->map(fn($m) => $this->formatMethod($m))->values()->toArray();
        });
    }

    /**
     * Tạo phương thức thanh toán mới.
     */
    public function store(array $data, string $adminId): ServiceReturn
    {
        return $this->execute(function () use ($data, $adminId): array {
            $minAmount = $data['min_amount'] ?? 10000;
            $maxAmount = $data['max_amount'] ?? 10000000;
            $this->validate($minAmount <= $maxAmount, 'Giới hạn số tiền nạp không hợp lệ.', 400);

            $isActive = $data['is_active'] ?? false;
            $type = $data['type'];

            // Kiểm tra kết nối nếu kích hoạt phương thức ví điện tử hoặc thẻ ngân hàng
            if ($isActive && in_array($type, ['e_wallet', 'bank_card'])) {
                $endpoint = $data['metadata']['endpoint'] ?? null;
                if ($endpoint) {
                    $this->pingPaymentService($endpoint);
                }
            }

            $method = $this->paymentMethodRepository->create([
                'type'          => $type,
                'code'          => $data['code'],
                'name'          => $data['name'],
                'is_active'     => $isActive,
                'min_amount'    => $minAmount,
                'max_amount'    => $maxAmount,
                'transfer_info' => $data['transfer_info'] ?? null,
                'icon_url'      => $data['icon_url'] ?? null,
                'metadata'      => $data['metadata'] ?? null,
                'sort_order'    => $data['sort_order'] ?? 0,
                'updated_by'    => $adminId,
            ]);

            // Audit log
            \Illuminate\Support\Facades\Log::info('Admin created top-up payment method configuration', [
                'action'       => 'create_payment_method',
                'admin_id'     => $adminId,
                'method_code'  => $method->code,
                'changes'      => $data,
                'occurred_at'  => now()->toIso8601String(),
            ]);

            return $this->formatMethod($method);
        }, useTransaction: true);
    }

    /**
     * Cập nhật phương thức thanh toán.
     */
    public function update(string $id, array $data, string $adminId, bool $confirm = false): ServiceReturn
    {
        return $this->execute(function () use ($id, $data, $adminId, $confirm) {
            $method = $this->paymentMethodRepository->find($id);
            $this->validate($method !== null, 'Không tìm thấy phương thức thanh toán.', 404);

            $minAmount = isset($data['min_amount']) ? (float) $data['min_amount'] : (float) $method->min_amount;
            $maxAmount = isset($data['max_amount']) ? (float) $data['max_amount'] : (float) $method->max_amount;
            $this->validate($minAmount <= $maxAmount, 'Giới hạn số tiền nạp không hợp lệ.', 400);

            // Kiểm tra trạng thái is_active mới
            $newActiveStatus = isset($data['is_active']) ? (bool) $data['is_active'] : (bool) $method->is_active;

            if ($method->is_active && !$newActiveStatus) {
                // Đang chuyển từ bật -> tắt, kiểm tra giao dịch pending
                if ($this->topUpRepository->hasPendingTopUps($method->code)) {
                    $this->validate($confirm, 'Phương thức này đang có giao dịch chờ xử lý. Bạn có chắc muốn vô hiệu hóa?', 400);
                }
            }

            // Kiểm tra kết nối nếu kích hoạt phương thức ví điện tử hoặc thẻ ngân hàng
            if ($newActiveStatus && in_array($method->type->value, ['e_wallet', 'bank_card'])) {
                $oldEndpoint = $method->metadata['endpoint'] ?? null;
                $newEndpoint = $data['metadata']['endpoint'] ?? $oldEndpoint;
                if ($newEndpoint && ($newEndpoint !== $oldEndpoint || !$method->is_active)) {
                    $this->pingPaymentService($newEndpoint);
                }
            }

            $updateData = array_filter([
                'name'          => $data['name'] ?? null,
                'is_active'     => isset($data['is_active']) ? (bool) $data['is_active'] : null,
                'min_amount'    => isset($data['min_amount']) ? (float) $data['min_amount'] : null,
                'max_amount'    => isset($data['max_amount']) ? (float) $data['max_amount'] : null,
                'transfer_info' => $data['transfer_info'] ?? null,
                'icon_url'      => $data['icon_url'] ?? null,
                'metadata'      => $data['metadata'] ?? null,
                'sort_order'    => isset($data['sort_order']) ? (int) $data['sort_order'] : null,
                'updated_by'    => $adminId,
            ], fn($v) => $v !== null);

            $this->paymentMethodRepository->updateById($id, $updateData);

            $updatedMethod = $this->paymentMethodRepository->find($id);

            // Audit log
            \Illuminate\Support\Facades\Log::info('Admin updated top-up payment method configuration', [
                'action'       => 'update_payment_method',
                'admin_id'     => $adminId,
                'method_code'  => $method->code,
                'changes'      => $data,
                'occurred_at'  => now()->toIso8601String(),
            ]);

            $activeCount = $this->paymentMethodRepository->getActiveMethods()->count();
            $formatted = $this->formatMethod($updatedMethod);

            if ($activeCount === 0) {
                return ServiceReturn::success($formatted, 'Hiện không có phương thức nạp tiền nào đang hoạt động. Driver sẽ không thể nạp tiền.');
            }

            return $formatted;
        }, useTransaction: true);
    }

    /**
     * Bật/tắt phương thức thanh toán.
     */
    public function toggle(string $id, string $adminId, bool $confirm = false): ServiceReturn
    {
        return $this->execute(function () use ($id, $adminId, $confirm): ServiceReturn {
            $method = $this->paymentMethodRepository->find($id);
            $this->validate($method !== null, 'Không tìm thấy phương thức thanh toán.', 404);

            $newStatus = !$method->is_active;

            if ($method->is_active && !$newStatus) {
                // Đang chuyển từ bật -> tắt, kiểm tra giao dịch pending
                if ($this->topUpRepository->hasPendingTopUps($method->code)) {
                    $this->validate($confirm, 'Phương thức này đang có giao dịch chờ xử lý. Bạn có chắc muốn vô hiệu hóa?', 400);
                }
            }

            // Kiểm tra kết nối nếu kích hoạt phương thức
            if ($newStatus && in_array($method->type->value, ['e_wallet', 'bank_card'])) {
                $endpoint = $method->metadata['endpoint'] ?? null;
                if ($endpoint) {
                    $this->pingPaymentService($endpoint);
                }
            }

            $this->paymentMethodRepository->updateById($id, [
                'is_active'  => $newStatus,
                'updated_by' => $adminId,
            ]);

            // Audit log
            \Illuminate\Support\Facades\Log::info('Admin toggled top-up payment method status', [
                'action'       => 'toggle_payment_method',
                'admin_id'     => $adminId,
                'method_code'  => $method->code,
                'new_status'   => $newStatus,
                'occurred_at'  => now()->toIso8601String(),
            ]);

            $activeCount = $this->paymentMethodRepository->getActiveMethods()->count();

            $data = [
                'id'        => (string) $method->id,
                'code'      => $method->code,
                'is_active' => $newStatus,
            ];

            if ($activeCount === 0) {
                return ServiceReturn::success($data, 'Hiện không có phương thức nạp tiền nào đang hoạt động. Driver sẽ không thể nạp tiền.');
            }

            return ServiceReturn::success($data, $newStatus ? 'Đã bật phương thức thanh toán.' : 'Đã tắt phương thức thanh toán.');
        }, useTransaction: true);
    }

    /**
     * Thử kết nối đến endpoint dịch vụ bên thứ ba.
     */
    private function pingPaymentService(string $endpoint): void
    {
        try {
            $host = parse_url($endpoint, PHP_URL_HOST);
            if ($host && !in_array($host, ['localhost', '127.0.0.1', 'example.com'])) {
                \Illuminate\Support\Facades\Http::timeout(2)->get($endpoint);
            }
        } catch (\Throwable $e) {
            $this->throw('Không thể kết nối đến dịch vụ thanh toán. Vui lòng kiểm tra cấu hình.', 400);
        }
    }

    private function formatMethod($method): array
    {
        return [
            'id'            => (string) $method->id,
            'type'          => $method->type->value,
            'type_label'    => $method->type->getLabel(),
            'code'          => $method->code,
            'name'          => $method->name,
            'is_active'     => (bool) $method->is_active,
            'min_amount'    => (float) $method->min_amount,
            'max_amount'    => (float) $method->max_amount,
            'transfer_info' => $method->transfer_info,
            'icon_url'      => $method->icon_url,
            'metadata'      => $method->metadata,
            'sort_order'    => (int) $method->sort_order,
            'updated_at'    => $method->updated_at?->toIso8601String(),
        ];
    }
}
