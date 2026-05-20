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
            'file' => [
                'required',
                'file',
                'max:5120',
                function ($attribute, $value, $fail) {
                    $extension = strtolower($value->getClientOriginalExtension());
                    $allowedExtensions = ['csv', 'txt', 'xls', 'xlsx', 'xlse'];
                    if (!in_array($extension, $allowedExtensions)) {
                        $fail('File phải thuộc định dạng csv, txt, xls, xlsx hoặc xlse.');
                    }
                }
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Vui lòng chọn file mẫu để nhập.',
            'file.file'     => 'File không hợp lệ.',
            'file.max'      => 'File vượt quá dung lượng cho phép (tối đa 5MB).',
        ];
    }
}
