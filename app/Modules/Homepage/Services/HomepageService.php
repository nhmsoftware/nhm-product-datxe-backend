<?php

declare(strict_types=1);

namespace App\Modules\Homepage\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceException;
use App\Core\Services\ServiceReturn;
use App\Modules\Homepage\Interfaces\HomepageServiceInterface;
use App\Modules\User\Interfaces\MerchantProfileRepositoryInterface;
use App\Modules\User\Interfaces\SavedAddressRepositoryInterface;
use App\Modules\User\Model\User;
use App\Modules\Marketing\Interfaces\BannerRepositoryInterface;
use App\Modules\Marketing\Interfaces\NewsRepositoryInterface;

class HomepageService extends BaseService implements HomepageServiceInterface
{
    protected SavedAddressRepositoryInterface $savedAddressRepo;
    protected BannerRepositoryInterface $bannerRepo;
    protected NewsRepositoryInterface $newsRepo;
    protected MerchantProfileRepositoryInterface $merchantProfileRepo;

    public function __construct(
        SavedAddressRepositoryInterface $savedAddressRepo,
        BannerRepositoryInterface $bannerRepo,
        NewsRepositoryInterface $newsRepo,
        MerchantProfileRepositoryInterface $merchantProfileRepo
    ) {
        $this->savedAddressRepo = $savedAddressRepo;
        $this->bannerRepo = $bannerRepo;
        $this->newsRepo = $newsRepo;
        $this->merchantProfileRepo = $merchantProfileRepo;
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
     * Lấy danh sách banner khuyến mãi.
     */
    private function getBanners(): array
    {
        $banners = $this->bannerRepo->getActiveBanners();
        return $banners->map(function ($banner) {
            return [
                'id' => $banner->id,
                'title' => $banner->title,
                'description' => $banner->description,
                'label' => $banner->label,
                'tag' => $banner->tag,
                'image' => $banner->image_url,
                'action_url' => $banner->action_url,
            ];
        })->toArray();
    }

    /**
     * Lấy danh sách tin tức & khuyến mãi.
     */
    private function getNewsPromotions(): array
    {
        $news = $this->newsRepo->getActiveNews();
        return $news->map(function ($item) {
            return [
                'id' => $item->id,
                'title' => $item->title,
                'description' => $item->description,
                'image' => $item->image_url,
                'tag' => $item->tag,
            ];
        })->toArray();
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

        $merchants = $this->merchantProfileRepo->getRandomActiveMerchants(5);

        $items = $merchants->map(function ($merchant) {
            // TODO: Sử dụng tọa độ thực để tính toán khoảng cách nếu cần.
            // Xếp hạng và khoảng cách của trình giữ chỗ vì chúng ta có thể chưa tính toán chặt chẽ trong truy vấn DB
            $rating = $merchant->average_rating ? number_format((float) $merchant->average_rating, 1) : '5.0';

            return [
                'id' => $merchant->id,
                'name' => $merchant->store_name ?? 'Cửa hàng',
                'imageAssetPath' => $merchant->store_image ?: 'assets/images/img_promotion_default.png',
                'rating' => $rating,
                'timeText' => '15 phut',
                'distanceText' => '2.4 km',
                'badgeLabel' => 'BAN CHAY',
            ];
        })->toArray();

        return [
            'status' => 'Lấy thành công',
            'items' => $items
        ];
    }
}
