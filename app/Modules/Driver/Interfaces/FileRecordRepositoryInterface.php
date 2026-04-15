<?php

declare(strict_types=1);

namespace App\Modules\Driver\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use App\Modules\Driver\Model\Enums\FileDisk;
use App\Modules\Driver\Model\Enums\FileableType;
use App\Modules\Driver\Model\FileRecord;

interface FileRecordRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Lưu tài liệu vào bảng `files` gắn với hồ sơ KYC.
     * UC-30 bước 7, A13.
     *
     * @param int          $fileableId    ID của hồ sơ (user_review_applications.id)
     * @param FileableType $fileableType  Loại tài liệu
     * @param string       $name          Tên đã đổi (UUID.ext)
     * @param string       $realName      Tên gốc từ client
     * @param string       $path          Đường dẫn trong storage
     * @param FileDisk     $disk          Disk lưu trữ
     * @param int          $size          Kích thước bytes
     * @param string       $mimeType      MIME type
     */
    public function storeFile(
        int          $fileableId,
        FileableType $fileableType,
        string       $name,
        string       $realName,
        string       $path,
        FileDisk     $disk,
        int          $size,
        string       $mimeType,
    ): FileRecord;
}
