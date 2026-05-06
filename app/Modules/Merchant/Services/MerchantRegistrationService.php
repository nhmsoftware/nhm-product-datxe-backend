<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Auth\DTO\SendOtpDTO;
use App\Modules\Auth\Interfaces\AuthOtpRepositoryInterface;
use App\Modules\Auth\Interfaces\AuthServiceInterface;
use App\Modules\Merchant\DTO\RegisterMerchantDTO;
use App\Modules\Merchant\Events\MerchantRegistrationSubmitted;
use App\Modules\Merchant\Interfaces\MerchantRegistrationServiceInterface;
use App\Modules\Merchant\Interfaces\MerchantRepositoryInterface;
use App\Modules\User\Interfaces\UserRepositoryInterface;
use App\Modules\User\Model\Enums\KycStatus;
use App\Modules\User\Model\Enums\UserOtpType;
use App\Modules\User\Model\Enums\UserRole;

final class MerchantRegistrationService extends BaseService implements MerchantRegistrationServiceInterface
{
    public function __construct(
        private readonly MerchantRepositoryInterface $merchantRepository,
        private readonly UserRepositoryInterface     $userRepository,
        private readonly AuthServiceInterface         $authService,
        private readonly AuthOtpRepositoryInterface  $authOtpRepository,
    ) {}

    public function submitRegistration(RegisterMerchantDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto): array {
            // 1. Kiểm tra User tồn tại
            $user = $this->userRepository->findById($dto->userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin người dùng.', 404);
            $this->validate(!$user->isMerchant(), 'Bạn đã là Merchant.', 400);

            // 2. Kiểm tra OTP đã được xác thực chưa
            $verifiedOtp = $this->authOtpRepository->getLastVerified($dto->phone, UserOtpType::VERIFY_MERCHANT_REGISTER);
            $this->validate($verifiedOtp !== null, 'Vui lòng xác thực số điện thoại trước khi gửi đăng ký.', 400);

            // 3. Kiểm tra CCCD không trùng
            $citizenIdExists = $this->merchantRepository->isCitizenIdExists($dto->citizenId, $dto->userId);
            $this->validate(!$citizenIdExists, 'CCCD đã được sử dụng.', 400);

            // 4. Kiểm tra tên cửa hàng không trùng
            $storeNameExists = $this->merchantRepository->isStoreNameExists($dto->storeName, $dto->userId);
            $this->validate(!$storeNameExists, 'Tên cửa hàng đã tồn tại.', 400);

            // 5. Cập nhật thông tin User (CCCD)
            $this->userRepository->updateById($dto->userId, [
                'citizen_id' => $dto->citizenId,
            ]);

            // 6. Tạo hồ sơ Merchant
            $merchantProfile = $this->merchantRepository->create([
                'user_id'                => $dto->userId,
                'store_name'             => $dto->storeName,
                'store_address'          => $dto->storeAddress,
                'business_type'          => $dto->businessType,
                'citizen_id_image'       => $dto->citizenIdImage,
                'business_license_image' => $dto->businessLicenseImage,
                'store_image'            => $dto->storeImage,
                'status'                 => KycStatus::Pending->value,
                'is_open'                => false, // Mặc định đóng khi chưa duyệt
            ]);

            // 7. Đánh dấu OTP đã sử dụng
            $this->authOtpRepository->markLatestAsUsed($dto->phone, UserOtpType::VERIFY_MERCHANT_REGISTER);

            // 8. Phát event
            event(new MerchantRegistrationSubmitted(
                userId:            $dto->userId,
                merchantProfileId: (string) $merchantProfile->id,
                storeName:         $dto->storeName
            ));

            return [
                'merchant_profile_id' => (string) $merchantProfile->id,
                'status'              => KycStatus::Pending->label(),
                'message'             => 'Đăng ký thành công. Vui lòng chờ xét duyệt.',
            ];
        }, useTransaction: true);
    }

    public function sendOtp(string $userId, string $phone): ServiceReturn
    {
        $dto = new SendOtpDTO(phone: $phone, type: UserOtpType::VERIFY_MERCHANT_REGISTER);
        return $this->authService->sendOtp($dto);
    }

    public function verifyOtp(string $userId, string $otp): ServiceReturn
    {
        return $this->execute(function () use ($userId, $otp): bool {
            $user = $this->userRepository->findById($userId);
            $this->validate($user !== null, 'Không tìm thấy người dùng.', 404);

            $lastOtp = $this->authOtpRepository->getLastOtp($user->phone, UserOtpType::VERIFY_MERCHANT_REGISTER);
            
            $this->validate($lastOtp !== null, 'Không tìm thấy mã OTP.', 404);
            $this->validate(!$lastOtp->isExpired(), 'OTP đã hết hạn.', 400);
            $this->validate($lastOtp->checkCode($otp), 'Mã OTP không đúng.', 400);

            $this->authOtpRepository->markAsVerified($lastOtp);

            return true;
        });
    }
}
