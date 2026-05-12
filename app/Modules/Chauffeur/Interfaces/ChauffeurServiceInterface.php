<?php

declare(strict_types=1);

namespace App\Modules\Chauffeur\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\Chauffeur\DTO\BookChauffeurDTO;

/**
 * Interface cho Chauffeur Service.
 * Tuân thủ nguyên tắc Interface-first của dự án.
 */
interface ChauffeurServiceInterface
{
    /**
     * Đặt dịch vụ Lái hộ (UC-124).
     *
     * @param BookChauffeurDTO $dto
     * @return ServiceReturn
     */
    public function bookChauffeur(BookChauffeurDTO $dto): ServiceReturn;
}
