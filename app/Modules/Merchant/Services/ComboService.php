<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Merchant\DTO\ComboDTO;
use App\Modules\Merchant\Interfaces\ComboItemRepositoryInterface;
use App\Modules\Merchant\Interfaces\ComboRepositoryInterface;
use App\Modules\Merchant\Interfaces\ComboServiceInterface;
use Illuminate\Support\Facades\Log;

final class ComboService extends BaseService implements ComboServiceInterface
{
    public function __construct(
        private readonly ComboRepositoryInterface $comboRepository,
        private readonly ComboItemRepositoryInterface $comboItemRepository,
    ) {}

    public function getMerchantCombos(string $merchantProfileId): ServiceReturn
    {
        return $this->execute(function () use ($merchantProfileId) {
            return $this->comboRepository->getByMerchant($merchantProfileId)->toArray();
        });
    }

    public function getComboDetail(string $comboId, string $merchantProfileId): ServiceReturn
    {
        return $this->execute(function () use ($comboId, $merchantProfileId) {
            $combo = $this->comboRepository->findWithDetails($comboId);
            $this->validate($combo !== null, 'Combo không tồn tại.', 404);
            $this->validate($combo->merchant_profile_id === $merchantProfileId, 'Bạn không có quyền xem combo này.', 403);

            return $combo->toArray();
        });
    }

    public function createCombo(ComboDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $combo = $this->comboRepository->create([
                'merchant_profile_id' => $dto->merchantProfileId,
                'name'                => $dto->name,
                'description'         => $dto->description,
                'price'               => $dto->price,
                'image_path'          => $dto->imagePath,
                'is_available'        => $dto->isAvailable,
                'order'               => $dto->order,
            ]);

            $this->syncItems((string) $combo->id, $dto->items);

            event(new \App\Modules\Merchant\Events\ComboCreated((string) $combo->id, $dto->merchantProfileId));

            return $combo->load('items')->toArray();
        }, useTransaction: true);
    }

    public function updateCombo(string $comboId, ComboDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($comboId, $dto) {
            $combo = $this->comboRepository->find($comboId);
            $this->validate($combo !== null, 'Combo không tồn tại.', 404);
            $this->validate($combo->merchant_profile_id === $dto->merchantProfileId, 'Bạn không có quyền sửa combo này.', 403);

            $this->comboRepository->updateById($comboId, [
                'name'         => $dto->name,
                'description'  => $dto->description,
                'price'        => $dto->price,
                'image_path'   => $dto->imagePath,
                'is_available' => $dto->isAvailable,
                'order'        => $dto->order,
            ]);

            $this->syncItems($comboId, $dto->items);

            event(new \App\Modules\Merchant\Events\ComboUpdated((string) $comboId, $dto->merchantProfileId));

            return $this->comboRepository->findWithDetails($comboId)->toArray();
        }, useTransaction: true);
    }

    public function deleteCombo(string $comboId, string $merchantProfileId): ServiceReturn
    {
        return $this->execute(function () use ($comboId, $merchantProfileId) {
            $combo = $this->comboRepository->findWithTrashed($comboId);
            $this->validate($combo !== null, 'Combo không tồn tại.', 404);
            $this->validate($combo->merchant_profile_id === $merchantProfileId, 'Bạn không có quyền xóa combo này.', 403);
            $this->validate(!$combo->trashed(), 'Combo đã bị xóa trước đó.', 400);

            $this->comboRepository->deleteById($comboId);
            
            event(new \App\Modules\Merchant\Events\ComboDeleted((string) $comboId, $merchantProfileId));
            
            return true;
        }, useTransaction: true);
    }

    public function updateStatus(string $comboId, string $merchantProfileId, bool $isAvailable): ServiceReturn
    {
        return $this->execute(function () use ($comboId, $merchantProfileId, $isAvailable) {
            $combo = $this->comboRepository->find($comboId);
            $this->validate($combo !== null, 'Combo không tồn tại.', 404);
            $this->validate($combo->merchant_profile_id === $merchantProfileId, 'Bạn không có quyền cập nhật combo này.', 403);

            $this->comboRepository->updateById($comboId, ['is_available' => $isAvailable]);
            
            event(new \App\Modules\Merchant\Events\ComboUpdated((string) $comboId, $merchantProfileId));
            
            return true;
        }, useTransaction: true);
    }

    private function syncItems(string $comboId, array $items): void
    {
        $this->comboItemRepository->deleteByCombo($comboId);
        foreach ($items as $item) {
            $this->comboItemRepository->create([
                'combo_id'     => $comboId,
                'menu_item_id' => $item['menu_item_id'],
                'quantity'     => $item['quantity'],
            ]);
        }
    }
}
