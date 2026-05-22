<?php

declare(strict_types=1);

namespace App\Core\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Helper tập trung xử lý upload & tạo URL cho file.
 *
 * Nguyên tắc:
 *  - TẤT CẢ file đều lưu vào local disk (storage/app/private) — KHÔNG dùng public disk.
 *  - URL trả về frontend là API endpoint có xác thực, KHÔNG phải public URL /storage/.
 *  - Chỉ dùng FileHelper::serveUrl() để sinh URL, không bao giờ ghép tay.
 */
final class FileHelper
{
    /**
     * Upload file vào local (private) disk.
     *
     * @param UploadedFile $file   File cần upload
     * @param string       $folder Thư mục đích (vd: 'banners', 'news', 'merchant/menu-items')
     * @return string              Path tương đối trong local disk (vd: 'banners/uuid.jpg')
     */
    public static function uploadToPrivate(UploadedFile $file, string $folder): string
    {
        $fileName = Str::uuid() . '.' . $file->extension();
        $path     = $file->storeAs($folder, $fileName, 'local');

        if ($path === false) {
            throw new \RuntimeException('Tải file thất bại. Vui lòng thử lại.');
        }

        return $path;
    }

    /**
     * Xóa file khỏi local disk.
     */
    public static function deleteFromPrivate(?string $path): void
    {
        if ($path && Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
        }
    }

    /**
     * Sinh URL để serve file qua API endpoint (có xác thực).
     * Dùng route name 'files.serve' với path được encode.
     *
     * Format: /api/v1/files/serve?path=<encoded_path>
     *
     * @param string|null $path  Path lưu trong DB (có thể null hoặc đã là URL cũ)
     * @return string|null
     */
    public static function serveUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        // Nếu đã là URL cũ (http/https), trả nguyên để không bị lỗi với dữ liệu cũ
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        $baseUrl = rtrim(config('app.url'), '/');
        return $baseUrl . '/api/v1/files/serve?path=' . urlencode($path);
    }
}
