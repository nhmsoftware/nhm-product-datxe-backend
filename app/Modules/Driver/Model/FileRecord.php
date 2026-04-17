<?php

declare(strict_types=1);

namespace App\Modules\Driver\Model;

use App\Core\Traits\HasBigIntId;
use App\Modules\Driver\Model\Enums\FileDisk;
use App\Modules\Driver\Model\Enums\FileableType;
use Illuminate\Database\Eloquent\Model;

/**
 * Tài liệu đính kèm — polymorphic qua fileable_type + fileable_id.
 * Maps bảng `files` table (database.md G5).
 *
 * @property int          $id
 * @property string       $name
 * @property string       $real_name
 * @property string       $path
 * @property FileDisk     $disk
 * @property int          $size
 * @property string       $mime_type
 * @property FileableType $fileable_type
 * @property int          $fileable_id
 */
class FileRecord extends Model
{
    use HasBigIntId;

    protected $table = 'files';

    protected $fillable = [
        'name',
        'real_name',
        'path',
        'disk',
        'size',
        'mime_type',
        'fileable_type',
        'fileable_id',
    ];

    protected $casts = [
        'disk'          => FileDisk::class,
        'fileable_type' => FileableType::class,
        'size'          => 'integer',
    ];
}
