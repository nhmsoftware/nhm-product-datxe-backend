<?php

declare(strict_types=1);

namespace App\Modules\Food\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Finance\Interfaces\VoucherServiceInterface;
use App\Modules\Food\DTO\CreateFoodOrderDTO;
use App\Modules\Food\Events\FoodOrderCreated;
use App\Modules\Food\Interfaces\FoodOrderRepositoryInterface;
use App\Modules\Food\Interfaces\FoodOrderServiceInterface;
use App\Modules\Food\Model\Enums\FoodOrderStatus;
use App\Modules\Merchant\Interfaces\MerchantRepositoryInterface;
use App\Modules\Merchant\Interfaces\MenuItemRepositoryInterface;
use App\Modules\Pricing\DTO\PricingRequestDTO;
use App\Modules\Pricing\Interfaces\PricingServiceInterface;
use App\Modules\Ride\Interfaces\MapServiceInterface;
use App\Modules\Ride\Model\Enums\VehicleType;
use App\Modules\User\Interfaces\UserRepositoryInterface;

final class FoodOrderService extends BaseService implements FoodOrderServiceInterface
{
    public function __construct(
        private readonly FoodOrderRepositoryInterface $foodOrderRepository,
        private readonly MerchantRepositoryInterface $merchantRepository,
        private readonly MenuItemRepositoryInterface $menuItemRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly MapServiceInterface $mapService,
        private readonly PricingServiceInterface $pricingService,
        private readonly VoucherServiceInterface $voucherService,
    ) {}

    public function createOrder(CreateFoodOrderDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            // 1. Verify Customer
            $user = $this->userRepository->findById((string) $dto->customerId);
            $this->validate($user !== null, 'Không tìm thấy khách hàng.', 404);
            $this->validate($user->is_phone_verified, 'Vui lòng xác thực số điện thoại để tiếp tục.', 403);

            // 2. Verify Merchant
            $merchant = $this->merchantRepository->findById((string) $dto->merchantId);
            $this->validate($merchant !== null, 'Cửa hàng không tồn tại.', 404);
            $this->validate($merchant->is_open, 'Cửa hàng hiện đang đóng cửa.', 400);

            // 3. Calculate Totals
            $totals = $this->calculateOrderTotals($dto, $merchant);
            $this->validate(!$totals->isError(), $totals->getMessage());
            $totalsData = $totals->getData();

            // 4. Prepare Order Data
            $orderData = [
                'customer_id' => $dto->customerId,
                'merchant_id' => $dto->merchantId,
                'status' => FoodOrderStatus::PENDING->value,
                'subtotal_price' => $totalsData['subtotal'],
                'delivery_fee' => $totalsData['delivery_fee'],
                'service_fee' => $totalsData['service_fee'],
                'discount_amount' => $totalsData['discount_amount'],
                'total_price' => $totalsData['total'],
                'delivery_address' => $dto->deliveryAddress,
                'delivery_lat' => $dto->deliveryLat,
                'delivery_lng' => $dto->deliveryLng,
                'customer_phone' => $dto->customerPhone,
                'notes' => $dto->notes,
                'voucher_code' => $dto->voucherCode,
            ];

            // 5. Prepare Items Data
            $itemsData = $totalsData['items_snapshot'];

            // 6. Persist Order
            $order = $this->foodOrderRepository->createOrder($orderData, $itemsData);

            // 7. Dispatch Event
            event(new FoodOrderCreated(
                orderId: (string) $order->id,
                customerId: (string) $order->customer_id,
                merchantId: (string) $order->merchant_id,
                totalPrice: (float) $order->total_price
            ));

            return $this->success($order->toArray(), 'Đặt món thành công. Đang gửi tới cửa hàng.');
        }, true, 'CreateFoodOrder');
    }

    public function calculateEstimate(CreateFoodOrderDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $merchant = $this->merchantRepository->findById((string) $dto->merchantId);
            $this->validate($merchant !== null, 'Cửa hàng không tồn tại.', 404);

            $totals = $this->calculateOrderTotals($dto, $merchant);
            if ($totals->isError()) {
                $this->throw($totals->getMessage());
            }

            return $totals->getData();
        });
    }

    private function calculateOrderTotals(CreateFoodOrderDTO $dto, $merchant): ServiceReturn
    {
        $subtotal = 0.0;
        $itemsSnapshot = [];

        foreach ($dto->items as $itemDto) {
            $menuItem = $this->menuItemRepository->findItem((string) $itemDto->menuItemId);
            if (!$menuItem || !$menuItem->is_available) {
                return $this->error("Món ăn '{$itemDto->menuItemId}' không khả dụng.");
            }

            $itemPrice = (float) $menuItem->price;
            $optionsData = [];
            foreach ($itemDto->options as $optionDto) {
                $itemPrice += (float) $optionDto->price;
                $optionsData[] = [
                    'option_name' => $optionDto->optionName,
                    'option_value' => $optionDto->optionValue,
                    'price' => $optionDto->price,
                ];
            }

            $subtotal += $itemPrice * $itemDto->quantity;

            $itemsSnapshot[] = [
                'menu_item_id' => $itemDto->menuItemId,
                'name' => $menuItem->name,
                'quantity' => $itemDto->quantity,
                'price' => $itemPrice,
                'notes' => $itemDto->notes,
                'options' => $optionsData,
            ];
        }

        // Calculate Delivery Fee
        $matrix = $this->mapService->getDistanceMatrix(
            (float) $merchant->latitude,
            (float) $merchant->longitude,
            $dto->deliveryLat,
            $dto->deliveryLng
        );

        // Assume BIKE for food delivery pricing
        $pricingResult = $this->pricingService->calculatePrice(PricingRequestDTO::create(
            distance: $matrix->distance / 1000,
            duration: $matrix->duration / 60,
            vehicleType: VehicleType::BIKE->value,
            surgeMultiplier: 1.0
        ));

        if ($pricingResult->isError()) {
            return $pricingResult;
        }

        $deliveryFee = (float) $pricingResult->getData()->finalFare;
        
        // Service Fee (Fixed or % - simplified as fixed for now)
        $serviceFee = 2000.0;

        $totalBeforeDiscount = $subtotal + $deliveryFee + $serviceFee;

        // Voucher
        $discountAmount = 0.0;
        if ($dto->voucherCode) {
            $voucherResult = $this->voucherService->validateAndCalculateDiscount(
                (string) $dto->customerId,
                $dto->voucherCode,
                $totalBeforeDiscount,
                'food'
            );
            if ($voucherResult->isSuccess()) {
                $discountAmount = (float) $voucherResult->getData();
            }
        }

        $total = max(0, $totalBeforeDiscount - $discountAmount);

        return $this->success([
            'subtotal' => $subtotal,
            'delivery_fee' => $deliveryFee,
            'service_fee' => $serviceFee,
            'discount_amount' => $discountAmount,
            'total' => $total,
            'items_snapshot' => $itemsSnapshot,
            'distance_km' => $matrix->distance / 1000,
            'duration_minutes' => round($matrix->duration / 60),
        ]);
    }
}
