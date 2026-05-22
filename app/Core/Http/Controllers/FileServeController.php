<?php

declare(strict_types=1);

namespace App\Core\Http\Controllers;

use App\Core\Controller\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Controller phục vụ tất cả file private từ local disk.
 * Truy cập qua: GET /api/v1/files/serve?path=<encoded_path>
 *
 * Bảo mật:
 *  - Route này yêu cầu xác thực (auth:sanctum) — được cấu hình trong routes
 *  - File chỉ được đọc từ local disk (storage/app/private), không thể traverse ra ngoài
 *  - Path được validate để ngăn path traversal
 */
final class FileServeController extends BaseController
{
    public function serve(Request $request): BinaryFileResponse
    {
        $path = $request->query('path');

        if (empty($path)) {
            abort(400, 'Thiếu tham số path.');
        }

        // Ngăn path traversal: loại bỏ ../ và ký tự nguy hiểm
        $path = str_replace(['../', './', '\\'], '', $path);
        $path = ltrim($path, '/');

        if (empty($path)) {
            abort(400, 'Path không hợp lệ.');
        }

        if (!Storage::disk('local')->exists($path)) {
            abort(404, 'File không tồn tại.');
        }

        $fullPath = Storage::disk('local')->path($path);

        return response()->file($fullPath, [
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
