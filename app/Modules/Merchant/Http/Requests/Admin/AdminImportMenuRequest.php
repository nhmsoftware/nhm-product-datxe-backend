<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Http\Requests\Admin;

use App\Core\Traits\HandleApi;
use Illuminate\Foundation\Http\FormRequest;

final class AdminImportMenuRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Vui lòng chọn file CSV để nhập.',
            'file.file'     => 'File không hợp lệ.',
            'file.mimes'    => 'File phải thuộc định dạng csv hoặc txt.',
            'file.max'      => 'File vượt quá dung lượng cho phép (tối đa 5MB).',
        ];
    }
}
