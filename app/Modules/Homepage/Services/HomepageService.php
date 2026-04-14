<?php

declare(strict_types=1);

namespace App\Modules\Homepage\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceException;
use App\Core\Services\ServiceReturn;
use App\Modules\Homepage\Interfaces\HomepageServiceInterface;
use App\Modules\User\Interfaces\SavedAddressRepositoryInterface;
use App\Modules\User\Model\User;

class HomepageService extends BaseService implements HomepageServiceInterface
{
    protected SavedAddressRepositoryInterface $savedAddressRepo;

    public function __construct(SavedAddressRepositoryInterface $savedAddressRepo)
    {
        $this->savedAddressRepo = $savedAddressRepo;
    }

    /**
     * {@inheritdoc}
     */
    public function getHomepageData(?User $user, float $lat = null, float $lng = null): ServiceReturn
    {
        return $this->execute(function () use ($user, $lat, $lng) {
            if (!$user) {
                return $this->throw(message: "Không có người dùng", code: 401);
            }

            $data = [
                'header' => $this->getHeaderData($user),
                'search_placeholder' => 'Bạn muốn đi đâu?',
                'services' => $this->getServiceIcons(),
                'saved_addresses' => $this->getSavedAddresses($user),
                'banners' => $this->getBanners(),
                'news_promotions' => $this->getNewsPromotions(),
                'restaurant_suggestions' => $this->getRestaurantSuggestions($lat, $lng),
            ];

            return $data;
        });
    }

    /**
     * Lấy dữ liệu Header.
     */
    private function getHeaderData(?User $user): array
    {
        if ($user) {
            return [
                'greeting' => "Xin chào, " . ($user->full_name ?? 'Người dùng'),
                'avatar' => $user->avatar, // URL avatar
                'has_notification' => true,
            ];
        }

        return [
            'greeting' => "Xin chào",
            'avatar' => null,
            'has_notification' => false,
            'show_auth_buttons' => true,
        ];
    }

    /**
     * Lấy danh sách icon dịch vụ chính.
     */
    private function getServiceIcons(): array
    {
        return [
            ['id' => 'bike', 'name' => 'Xe máy', 'icon' => 'bike_icon_url', 'type' => 'book_ride'],
            ['id' => 'car', 'name' => 'Ô tô', 'icon' => 'car_icon_url', 'type' => 'book_ride'],
            ['id' => 'food', 'name' => 'Đồ ăn', 'icon' => 'food_icon_url', 'type' => 'order_food'],
            ['id' => 'delivery', 'name' => 'Giao hàng', 'icon' => 'delivery_icon_url', 'type' => 'delivery_order'],
            ['id' => 'intercity', 'name' => 'Đi tỉnh', 'icon' => 'intercity_icon_url', 'type' => 'book_ride'],
            ['id' => 'airport', 'name' => 'Sân bay', 'icon' => 'airport_icon_url', 'type' => 'book_ride'],
            ['id' => 'proxy_driver', 'name' => 'Lái hộ', 'icon' => 'proxy_driver_icon_url', 'type' => 'book_ride'],
            ['id' => 'assistant', 'name' => 'Đặt hộ', 'icon' => 'assistant_icon_url', 'type' => 'book_ride'],
        ];
    }

    /**
     * Lấy địa chỉ đã lưu của khách hàng.
     */
    private function getSavedAddresses(?User $user): array
    {
        if (!$user || !$user->isCustomer()) {
            return [];
        }

        $customerProfile = $user->customerProfile;
        if (!$customerProfile) {
            return [];
        }

        $addresses = $this->savedAddressRepo->getByCustomer($customerProfile);

        return $addresses->map(function ($addr) {
            return [
                'id' => $addr->id,
                'label' => $addr->label,
                'address' => $addr->address,
                'type' => $addr->type, // home, office, other
                'is_default' => $addr->is_default,
            ];
        })->toArray();
    }

    /**
     * Lấy danh sách banner khuyến mãi (Mockup).
     */
    private function getBanners(): array
    {
        return [
            ['id' => 1, 'image' => 'banner_1_url', 'action_url' => '/promo/1'],
            ['id' => 2, 'image' => 'banner_2_url', 'action_url' => '/promo/2'],
        ];
    }

    /**
     * Lấy danh sách tin tức & khuyến mãi (Mockup).
     */
    private function getNewsPromotions(): array
    {
        return [
            [
                'id' => 1,
                'title' => 'Giảm 50% cho Đồ ăn',
                'description' => 'Áp dụng cho đơn hàng từ 100k',
                'image' => 'news_1_url',
                'tag' => 'Khuyến mãi'
            ],
            [
                'id' => 2,
                'title' => 'Mã giảm giá đặc biệt',
                'description' => 'Nhập mã NHM50 giảm ngay 50k',
                'image' => 'news_2_url',
                'tag' => 'Tin mới'
            ],
        ];
    }

    /**
     * Lấy gợi ý quán ngon (Mockup).
     */
    private function getRestaurantSuggestions(?float $lat, ?float $lng): array
    {
        if (!$lat || !$lng) {
            // Trả về quán mặc định hoặc thông báo yêu cầu vị trí
            return [
                'status' => 'location_required',
                'message' => 'Vui lòng bật vị trí để xem quán gần bạn',
                'items' => []
            ];
        }

        return [
            'status' => 'success',
            'items' => [
                [
                    'id' => 1,
                    'name' => 'Phở Hùng - Nguyễn Trãi',
                    'image' => 'restaurant_1_url',
                    'rating' => 4.5,
                    'delivery_time' => '20 min',
                    'distance' => '1.2 km',
                    'promo_label' => 'Giá tốt'
                ],
                [
                    'id' => 2,
                    'name' => 'The Burger Joint',
                    'image' => 'restaurant_2_url',
                    'rating' => 4.8,
                    'delivery_time' => '30 min',
                    'distance' => '2.5 km',
                    'promo_label' => null
                ],
            ]
        ];
    }
}
