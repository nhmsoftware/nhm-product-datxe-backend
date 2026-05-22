<?php

declare(strict_types=1);

namespace App\Modules\Driver\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Driver\Interfaces\FileRecordRepositoryInterface;
use App\Modules\Driver\Model\FileRecord;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

use OpenApi\Attributes as OA;

/**
 * Controller để phục vụ các file KYC nằm trong local disk (không public).
 */
final class FileController extends BaseController
{
    public function __construct(
        private readonly FileRecordRepositoryInterface $fileRecordRepository
    ) {}
    /**
     * Trả về nội dung file ảnh/pdf từ storage.
     */
    #[OA\Get(
        path: '/api/v1/driver/files/{id}',
        summary: 'Xem nội dung ảnh/tài liệu KYC',
        tags: ['Driver|Files'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Trả về file binary (image/jpeg, image/png, application/pdf)'),
            new OA\Response(response: 404, description: 'Không tìm thấy file'),
        ]
    )]
    public function show(string $id): BinaryFileResponse
    {
        /** @var FileRecord|null $file */
        $file = $this->fileRecordRepository->findById($id);

        if (!$file) {
            abort(404, 'Không tìm thấy tài liệu.');
        }

        $disk = $file->disk->getDiskName(); // 'local' hoặc 'public'
        
        if (!Storage::disk($disk)->exists($file->path)) {
            abort(404, 'File không tồn tại trên hệ thống.');
        }

        return response()->file(Storage::disk($disk)->path($file->path));
    }
}
