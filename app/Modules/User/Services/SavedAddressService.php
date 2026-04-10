<?php

declare(strict_types=1);

namespace App\Modules\User\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\User\Interfaces\SavedAddressRepositoryInterface;
use App\Modules\User\Interfaces\SavedAddressServiceInterface;
use App\Modules\User\Model\CustomerProfile;
use App\Modules\User\Model\CustomerSavedAddress;
use App\Modules\User\Model\User;
use Illuminate\Support\Facades\DB;

class SavedAddressService extends BaseService implements SavedAddressServiceInterface
{
    // Số lượng địa chỉ tối đa cho mỗi khách hàng
    private const MAX_ADDRESSES = 10;

    public function __construct(
        private readonly SavedAddressRepositoryInterface $addressRepository
    ) {
    }

    /**
     * Lấy tất cả địa chỉ đã lưu của một khách hàng.
     */
    public function getAddresses(User $user): ServiceReturn
    {
        return $this->execute(function () use ($user) {
            $customerProfile = $this->getCustomerProfile($user);
            $addresses = $this->addressRepository->getByCustomer($customerProfile);
            return $addresses->map(fn ($address) => $this->formatAddress($address))->toArray();
        }, useTransaction: true);
    }

    /**
     * Lấy một địa chỉ đã lưu cụ thể.
     */
    public function getAddress(User $user, int $addressId): ServiceReturn
    {
        return $this->execute(function () use ($user, $addressId) {
            $address = $this->findAndVerifyAddress($user, $addressId);
            return $this->formatAddress($address);
        });
    }

    /**
     * Tạo một địa chỉ đã lưu mới.
     */
    public function createAddress(User $user, array $data): ServiceReturn
    {
        return $this->execute(function () use ($user, $data) {
            $customerProfile = $this->getCustomerProfile($user);

            // A4 - Kiểm tra giới hạn tối đa
            if ($this->addressRepository->countByCustomer($customerProfile) >= self::MAX_ADDRESSES) {
                $this->throw(
                    message: "Bạn chỉ có thể lưu tối đa " . self::MAX_ADDRESSES . " địa chỉ. Vui lòng xóa bớt địa chỉ cũ để thêm mới.",
                    code: 400
                );
            }

            // Kiểm tra địa chỉ trùng lặp
            $existingAddress = $this->findDuplicateAddress($customerProfile, $data);
            if ($existingAddress) {
                $this->throw(
                    message: 'Địa chỉ này đã được lưu trước đó.',
                    code: 422,
                );
            }

            // Nếu địa chỉ này được đặt làm mặc định, bỏ mặc định các địa chỉ khác trước
            if (($data['is_default'] ?? false)) {
                $this->addressRepository->unsetDefaults($customerProfile);
            }

            // Tạo địa chỉ
            $address = $this->addressRepository->createForCustomer($customerProfile, $data);

            if (!$address) {
                $this->throw(
                    message: 'Không thể tạo địa chỉ mới. Vui lòng thử lại.',
                    code: 500,
                );
            }

            return $this->formatAddress($address);
        }, useTransaction: true);
    }

    /**
     * Cập nhật một địa chỉ đã lưu.
     */
    public function updateAddress(User $user, int $addressId, array $data): ServiceReturn
    {
        return $this->execute(function () use ($user, $addressId, $data) {
            $address = $this->findAndVerifyAddress($user, $addressId);

            if (isset($data['lat']) || isset($data['lng'])) {
                $checkData = [
                    'lat' => $data['lat'] ?? $address->lat,
                    'lng' => $data['lng'] ?? $address->lng,
                ];

                $existingAddress = $this->findDuplicateAddress($address->customerProfile, $checkData, $address->id);

                if ($existingAddress) {
                    $this->throw(message: 'Địa chỉ này đã tồn tại trong danh sách của bạn.', code: 422);
                }
            }

            // Nếu địa chỉ này được đặt làm mặc định, bỏ mặc định các địa chỉ khác trước
            if (($data['is_default'] ?? false)) {
                $this->addressRepository->unsetDefaults($address->customerProfile, $address->id);
            }

            // Cập nhật địa chỉ
            $this->addressRepository->updateById($address->id, $data);

            return $this->formatAddress($address->fresh());
        }, useTransaction: true);
    }

    /**
     * Xóa một địa chỉ đã lưu.
     */
    public function deleteAddress(User $user, int $addressId): ServiceReturn
    {
        return $this->execute(function () use ($user, $addressId) {
            $address = $this->findAndVerifyAddress($user, $addressId);

            $wasDefault = $address->is_default;
            $customerProfile = $address->customerProfile; // Lưu lại customer profile trước khi xóa
            $this->addressRepository->deleteById($address->id);

            // Nếu địa chỉ bị xóa là mặc định, đặt một địa chỉ khác làm mặc định
            if ($wasDefault) {
                $newDefault = $this->addressRepository->findFirstByCustomer($customerProfile);
                if ($newDefault) {
                    $this->addressRepository->updateById($newDefault->id, ['is_default' => true]);
                }
            }

            return true; // Trả về true để chỉ ra xóa thành công
        }, useTransaction: true);
    }

    /**
     * Đặt một địa chỉ làm mặc định.
     */
    public function setAsDefault(User $user, int $addressId): ServiceReturn
    {
        return $this->execute(function () use ($user, $addressId) {
            $address = $this->findAndVerifyAddress($user, $addressId);

            // Bỏ mặc định tất cả các địa chỉ khác của khách hàng này
            $this->addressRepository->unsetDefaults($address->customerProfile, $address->id);

            // Đặt địa chỉ này làm mặc định
            $this->addressRepository->updateById($address->id, ['is_default' => true]);

            return $this->formatAddress($address->fresh());
        }, useTransaction: true);
    }

    /**
     * Helper: Kiểm tra vai trò và lấy CustomerProfile từ User.
     */
    private function getCustomerProfile(User $user): CustomerProfile
    {
        if (!$user->isCustomer()) {
            $this->throw('Chỉ khách hàng mới có thể sử dụng chức năng này.', 403);
        }

        $customerProfile = $user->customerProfile;

        if (!$customerProfile) {
            $this->throw('Không tìm thấy thông tin khách hàng.', 404);
        }

        return $customerProfile;
    }

    /**
     * Helper: Tìm địa chỉ bằng ID và xác minh quyền sở hữu.
     */
    private function findAndVerifyAddress(User $user, int $addressId): CustomerSavedAddress
    {
        $address = $this->addressRepository->findById($addressId);

        if (!$address) {
            $this->throw('Không tìm thấy địa chỉ.', 404);
        }

        // Lấy customer profile ID từ user để kiểm tra
        $customerProfileId = $this->getCustomerProfile($user)->id;

        if ($address->customer_id !== $customerProfileId) {
            $this->throw('Bạn không có quyền truy cập địa chỉ này.', 403);
        }

        return $address;
    }

    /**
     * Tìm địa chỉ trùng lặp trong bán kính nhỏ (khoảng 50 mét).
     */
    private function findDuplicateAddress(CustomerProfile $customerProfile, array $data, ?int $excludeId = null): ?CustomerSavedAddress
    {
        if (!isset($data['lat']) || !isset($data['lng'])) {
            return null;
        }

        return $this->addressRepository->findDuplicate(
            $customerProfile,
            (float) $data['lat'],
            (float) $data['lng'],
            $excludeId
        );
    }

    /**
     * Định dạng địa chỉ để trả về.
     */
    private function formatAddress(CustomerSavedAddress $address): array
    {
        return [
            'id' => $address->id,
            'label' => $address->label,
            'label_text' => $address->label_text,
            'name' => $address->name ?? $address->label_text,
            'address_text' => $address->address_text,
            'lat' => (float) $address->lat,
            'lng' => (float) $address->lng,
            'receiver_name' => $address->receiver_name,
            'receiver_phone' => $address->receiver_phone,
            'note' => $address->note,
            'is_default' => $address->is_default,
            'created_at' => $address->created_at?->toIso8601String(),
            'updated_at' => $address->updated_at?->toIso8601String(),
        ];
    }
}
