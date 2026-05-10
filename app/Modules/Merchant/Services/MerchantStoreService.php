<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Merchant\Interfaces\MerchantStoreServiceInterface;
use App\Modules\Merchant\Interfaces\MerchantRepositoryInterface;
use App\Modules\Order\Interfaces\OrderRepositoryInterface;

final class MerchantStoreService extends BaseService implements MerchantStoreServiceInterface
{
    public function __construct(
        private readonly MerchantRepositoryInterface $merchantRepository,
        private readonly OrderRepositoryInterface $orderRepository,
    ) {}

    public function getStoreInfo(string $userId): ServiceReturn
    {
        return $this->execute(function () use ($userId) {
            $store = $this->merchantRepository->query()
                ->with('openingHours')
                ->where('user_id', $userId)
                ->first();
                
            $this->validate($store !== null, 'Bạn chưa có cửa hàng.', 404);

            return $store->toArray();
        });
    }

    public function updateStatus(string $userId, bool $isOpen): ServiceReturn
    {
        return $this->execute(function () use ($userId, $isOpen) {
            $store = $this->merchantRepository->findByUserId($userId);
            $this->validate($store !== null, 'Bạn chưa có cửa hàng.', 404);

            // Giả lập check đơn hàng hoạt động (UC-55 A1)
            // Trong tương lai sẽ gọi OrderRepository::countActiveOrders($store->id)
            $activeOrdersCount = 0; 

            $store->update(['is_open' => $isOpen]);

            // Phát event (UC-55)
            event(new \App\Modules\Merchant\Events\MerchantStatusToggled((string)$store->id, $isOpen, $activeOrdersCount));

            return [
                'is_open'             => $store->is_open,
                'active_orders_count' => $activeOrdersCount,
                'message'             => $isOpen ? 'Đã mở cửa hàng.' : 'Đã đóng cửa hàng tạm thời.',
            ];
        }, useTransaction: true);
    }

    public function updateOperatingHours(string $userId, string $openingTime, string $closingTime): ServiceReturn
    {
        return $this->execute(function () use ($userId, $openingTime, $closingTime) {
            $store = $this->merchantRepository->findByUserId($userId);
            $this->validate($store !== null, 'Bạn chưa có cửa hàng.', 404);

            $store->update([
                'opening_time' => $openingTime,
                'closing_time' => $closingTime,
            ]);

            return $store->toArray();
        }, useTransaction: true);
    }

    public function updateWeeklySchedule(string $userId, array $schedule): ServiceReturn
    {
        return $this->execute(function () use ($userId, $schedule) {
            $store = $this->merchantRepository->findByUserId($userId);
            $this->validate($store !== null, 'Bạn chưa có cửa hàng.', 404);
            $this->validate($store->status === \App\Modules\User\Model\Enums\KycStatus::Approved, 'Cửa hàng của bạn chưa được duyệt.', 400);

            $processedSchedule = [];
            foreach ($schedule as $day) {
                $dayOfWeek = (int) $day['day_of_week'];
                $isClosed = (bool) ($day['is_closed'] ?? false);
                $opening = $day['opening_time'] ?? null;
                $closing = $day['closing_time'] ?? null;

                if (!$isClosed) {
                    $this->validate(!empty($opening) && !empty($closing), "Vui lòng nhập giờ mở/đóng cho ngày thứ {$dayOfWeek}.", 400);
                    
                    $isOvernight = false;
                    // Logic Overnight: nếu giờ đóng < giờ mở
                    if (strtotime($closing) < strtotime($opening)) {
                        $isOvernight = true;
                    }
                } else {
                    $opening = null;
                    $closing = null;
                    $isOvernight = false;
                }

                $processedSchedule[] = [
                    'day_of_week'  => $dayOfWeek,
                    'opening_time' => $opening,
                    'closing_time' => $closing,
                    'is_closed'    => $isClosed,
                    'is_overnight' => $isOvernight,
                ];
            }

            $this->merchantRepository->updateOpeningHoursSchedule((string) $store->id, $processedSchedule);

            return $processedSchedule;
        }, useTransaction: true);
    }

    public function updateDiscount(string $userId, float $commissionRate): ServiceReturn
    {
        return $this->execute(function () use ($userId, $commissionRate) {
            $store = $this->merchantRepository->findByUserId($userId);
            $this->validate($store !== null, 'Bạn chưa có cửa hàng.', 404);
            $this->validate($commissionRate >= 0 && $commissionRate <= 100, 'Mức chiết khấu không hợp lệ.', 400);

            $store->update(['commission_rate' => $commissionRate]);

            return $store->toArray();
        }, useTransaction: true);
    }

    public function getCommissionPackages(): array
    {
        return [
            [
                'key'         => 'BASIC',
                'name'        => 'Gói Cơ Bản',
                'rate'        => 20,
                'description' => 'Gói tiêu chuẩn, thuật toán xếp hạng hiển thị quán một cách tự nhiên dựa trên đánh giá.',
                'benefits'    => ['Xếp hạng tự nhiên', 'Không huy hiệu'],
            ],
            [
                'key'         => 'PRIORITY',
                'name'        => 'Gói Ưu Tiên',
                'rate'        => 25,
                'description' => 'Quán được gắn nhãn "Quán Ưu Tiên" và tự động đẩy lên đầu các mục gợi ý.',
                'benefits'    => ['Gắn nhãn Ưu Tiên', 'Đẩy lên đầu gợi ý', 'Hỗ trợ Freeship cho khách'],
            ],
            [
                'key'         => 'EXCLUSIVE',
                'name'        => 'Gói Độc Quyền',
                'rate'        => 15,
                'description' => 'Chiết khấu tốt nhất, cam kết chỉ bán duy nhất trên ứng dụng của chúng tôi.',
                'benefits'    => ['Chiết khấu thấp nhất', 'Cam kết độc quyền'],
            ],
        ];
    }

    public function updateCommissionPackage(string $userId, string $packageKey): ServiceReturn
    {
        return $this->execute(function () use ($userId, $packageKey) {
            $store = $this->merchantRepository->findByUserId($userId);
            $this->validate($store !== null, 'Bạn chưa có cửa hàng.', 404);

            $packages = $this->getCommissionPackages();
            $selectedPackage = null;
            foreach ($packages as $pkg) {
                if ($pkg['key'] === strtoupper($packageKey)) {
                    $selectedPackage = $pkg;
                    break;
                }
            }

            $this->validate($selectedPackage !== null, 'Gói chiết khấu không hợp lệ.', 400);

            $store->update([
                'commission_package' => $selectedPackage['key'],
                'commission_rate'    => $selectedPackage['rate'],
            ]);

            // Phát event (UC-56)
            event(new \App\Modules\Merchant\Events\MerchantCommissionUpdated((string)$store->id, $selectedPackage['key'], (float)$selectedPackage['rate']));

            return [
                'commission_package' => $store->commission_package,
                'commission_rate'    => $store->commission_rate,
                'message'            => "Đã cập nhật sang {$selectedPackage['name']} thành công.",
            ];
        }, useTransaction: true);
    }

    public function getDailyOrderStats(string $userId): ServiceReturn
    {
        return $this->execute(function () use ($userId) {
            $store = $this->merchantRepository->findByUserId($userId);
            $this->validate($store !== null, 'Bạn chưa có cửa hàng.', 404);

            $totalOrders = $this->orderRepository->countDailyOrdersByMerchant((string) $store->id);

            return [
                'total_orders_today' => $totalOrders,
                'date'               => now()->toDateString(),
            ];
        });
    }

    public function getDailyRevenueStats(string $userId): ServiceReturn
    {
        return $this->execute(function () use ($userId) {
            $store = $this->merchantRepository->findByUserId($userId);
            $this->validate($store !== null, 'Bạn chưa có cửa hàng.', 404);

            $totalRevenue = $this->orderRepository->sumDailyRevenueByMerchant((string) $store->id);

            return [
                'total_revenue_today' => $totalRevenue,
                'date'                => now()->toDateString(),
            ];
        });
    }
}
