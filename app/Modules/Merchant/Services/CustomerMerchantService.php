<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Merchant\DTO\GetNearbyMerchantsDTO;
use App\Modules\Merchant\Interfaces\CustomerMerchantServiceInterface;
use App\Modules\Merchant\Interfaces\MerchantRepositoryInterface;
use App\Modules\Merchant\Interfaces\MenuRepositoryInterface;

final class CustomerMerchantService extends BaseService implements CustomerMerchantServiceInterface
{
    public function __construct(
        private readonly MerchantRepositoryInterface $merchantRepository,
        private readonly MenuRepositoryInterface $menuRepository
    ) {}

    /**
     * @inheritDoc
     */
    public function getNearbyMerchants(GetNearbyMerchantsDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $paginator = $this->merchantRepository->getNearbyMerchants($dto);
            
            // Format output structure
            return [
                'items' => collect($paginator->items())->map(function ($merchant) {
                    return [
                        'id'             => (string) $merchant->id,
                        'store_name'     => $merchant->store_name,
                        'store_address'  => $merchant->store_address,
                        'lat'            => (float) $merchant->latitude,
                        'lng'            => (float) $merchant->longitude,
                        'opening_time'   => $merchant->opening_time,
                        'closing_time'   => $merchant->closing_time,
                        'is_open'        => (bool) $merchant->is_open,
                        'store_image'    => $merchant->store_image,
                        'average_rating' => (float) $merchant->average_rating,
                        'total_orders'   => (int) $merchant->total_orders,
                        'distance'       => round((float) $merchant->distance, 2), // in km
                        'opening_hours'  => $merchant->openingHours->toArray(),
                    ];
                })->toArray(),
                'pagination' => [
                    'total'        => $paginator->total(),
                    'count'        => $paginator->count(),
                    'per_page'     => $paginator->perPage(),
                    'current_page' => $paginator->currentPage(),
                    'total_pages'  => $paginator->lastPage(),
                ]
            ];
        });
    }

    /**
     * @inheritDoc
     */
    public function getMerchantDetail(string $id): ServiceReturn
    {
        return $this->execute(function () use ($id) {
            $merchant = $this->merchantRepository->getByIdForCustomer($id);

            $this->validate($merchant !== null, 'Không tìm thấy cửa hàng hoặc cửa hàng chưa được kích hoạt.', 404);

            return [
                'id'             => (string) $merchant->id,
                'store_name'     => $merchant->store_name,
                'store_address'  => $merchant->store_address,
                'lat'            => (float) $merchant->latitude,
                'lng'            => (float) $merchant->longitude,
                'opening_time'   => $merchant->opening_time,
                'closing_time'   => $merchant->closing_time,
                'is_open'        => (bool) $merchant->is_open,
                'store_image'    => $merchant->store_image,
                'average_rating' => (float) $merchant->average_rating,
                'total_orders'   => (int) $merchant->total_orders,
                'opening_hours'  => $merchant->openingHours->toArray(),
            ];
        });
    }

    /**
     * @inheritDoc
     */
    public function getMerchantMenu(string $id): ServiceReturn
    {
        return $this->execute(function () use ($id) {
            $merchant = $this->merchantRepository->getByIdForCustomer($id);
                
            $this->validate($merchant !== null, 'Không tìm thấy cửa hàng hoặc cửa hàng chưa được kích hoạt.', 404);

            // Truy vấn lấy thực đơn bao gồm các danh mục và món ăn đang được bán qua Repository
            $categories = $this->menuRepository->getFullMenuForCustomer($id);

            return $categories->map(function ($category) {
                return [
                    'id'    => (string) $category->id,
                    'name'  => $category->name,
                    'order' => (int) $category->order,
                    'items' => $category->items->map(function ($item) {
                        return [
                            'id'           => (string) $item->id,
                            'name'         => $item->name,
                            'description'  => $item->description,
                            'price'        => (float) $item->price,
                            'image_path'   => $item->image_path,
                            'is_available' => (bool) $item->is_available,
                            'order'        => (int) $item->order,
                            'rating'       => (float) $item->rating,
                            'sizes'        => $item->sizes->map(function ($size) {
                                return [
                                    'id'         => (string) $size->id,
                                    'name'       => $size->name,
                                    'price'      => (float) $size->price,
                                    'is_default' => (bool) $size->is_default,
                                ];
                            })->toArray(),
                            'toppings'     => $item->toppings->map(function ($topping) {
                                return [
                                    'id'           => (string) $topping->id,
                                    'name'         => $topping->name,
                                    'price'        => (float) $topping->price,
                                    'max_quantity' => (int) $topping->max_quantity,
                                    'is_required'  => (bool) $topping->is_required,
                                ];
                            })->toArray(),
                        ];
                    })->toArray(),
                ];
            })->toArray();
        });
    }
}
