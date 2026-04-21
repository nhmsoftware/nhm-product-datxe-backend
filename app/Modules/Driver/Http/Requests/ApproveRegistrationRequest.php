<?php

declare(strict_types=1);

namespace App\Modules\Driver\Http\Requests;

use App\Modules\Driver\Model\Enums\KycStatus;
use App\Core\Traits\HandleApi;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

final class ApproveRegistrationRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true; // Admin middleware will handle this
    }

    public function rules(): array
    {
        return [
            'driver_group_id' => [
                'nullable',
                'integer',
                Rule::exists('driver_groups', 'id')->whereNull('deleted_at'),
            ],
        ];
    }

    public function all($keys = null): array
    {
        $data = parent::all($keys);
        $data['id'] = $this->route('id');
        return $data;
    }

    /**
     * @throws HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->sendValidation('Dữ liệu không hợp lệ.', $validator->errors()->toArray(), 400)
        );
    }
}
