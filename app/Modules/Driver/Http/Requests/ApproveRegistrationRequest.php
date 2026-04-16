<?php

declare(strict_types=1);

namespace App\Modules\Driver\Http\Requests;

use App\Modules\Driver\Model\Enums\KycStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ApproveRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Admin middleware will handle this
    }

    public function rules(): array
    {
        return [
            // id được lấy từ route, nhưng ta có thể validate nó ở đây nếu cần
            // Ở đây ta chỉ cần check xem application_id từ route có tồn tại và đang PENDING không
        ];
    }

    public function all($keys = null): array
    {
        $data = parent::all($keys);
        $data['id'] = $this->route('id');
        return $data;
    }
}
