<?php

declare(strict_types=1);

namespace App\Modules\Driver\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Driver\Interfaces\FileRecordRepositoryInterface;
use App\Modules\Driver\Model\Enums\FileDisk;
use App\Modules\Driver\Model\Enums\FileableType;
use App\Modules\Driver\Model\FileRecord;
use Illuminate\Support\Facades\Log;

final class FileRecordRepository extends BaseRepository implements FileRecordRepositoryInterface
{
    public function getModel(): string
    {
        return FileRecord::class;
    }

    /**
     * Lưu tài liệu vào bảng `files` — UC-30 bước 7.
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
    ): FileRecord {
        try {
            /** @var FileRecord */
            return $this->model->create([
                'name'          => $name,
                'real_name'     => $realName,
                'path'          => $path,
                'disk'          => $disk->value,
                'size'          => $size,
                'mime_type'     => $mimeType,
                'fileable_type' => $fileableType->value,
                'fileable_id'   => $fileableId,
            ]);
        } catch (\Exception $e) {
            Log::error('FileRecordRepository::storeFile failed', [
                'fileable_id'   => $fileableId,
                'fileable_type' => $fileableType->name,
                'exception'     => $e->getMessage(),
            ]);
            throw new \App\Core\Exceptions\InfrastructureException(
                'Không thể lưu tài liệu. Vui lòng thử lại.',
                500
            );
        }
    }
}
