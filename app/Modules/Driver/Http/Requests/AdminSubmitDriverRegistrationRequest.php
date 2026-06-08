<?php

declare(strict_types=1);

namespace App\Modules\Driver\Http\Requests;

use App\Core\Traits\HandleApi;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

final class AdminSubmitDriverRegistrationRequest extends FormRequest
{
    use HandleApi;

    private const ALLOWED_MIMES = 'jpeg,jpg,png,pdf';
    private const MAX_SIZE_KB = 5120;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxYear = now()->year;

        return [
            'full_name' => 'required|string|max:100',
            'phone' => ['required', 'string', 'regex:/^(0[3|5|7|8|9])+([0-9]{8})$/'],
            'citizen_id' => ['required', 'string', 'regex:/^[0-9]{12}$/'],
            'vehicle_type' => 'required|integer|in:1,2,3,4',
            'vehicle_name' => 'required|string|max:255',
            'vehicle_color' => 'required|integer|in:0,1,2,3,4,5,6,7,8,9',
            'vehicle_number' => 'required|string|max:20',
            'vehicle_year' => "required|integer|min:1990|max:{$maxYear}",
            'services' => 'required|array|min:1',
            'services.*' => 'integer|in:1,2,3,4,5,6,7,8',
            'cccd_front' => "required|file|mimes:{$this->mimes()}|max:" . self::MAX_SIZE_KB,
            'cccd_back' => "required|file|mimes:{$this->mimes()}|max:" . self::MAX_SIZE_KB,
            'driver_license' => "required|file|mimes:{$this->mimes()}|max:" . self::MAX_SIZE_KB,
            'vehicle_reg' => "required|file|mimes:{$this->mimes()}|max:" . self::MAX_SIZE_KB,
            'criminal_record' => "required|file|mimes:{$this->mimes()}|max:" . self::MAX_SIZE_KB,
            'health_cert' => "required|file|mimes:{$this->mimes()}|max:" . self::MAX_SIZE_KB,
            'portrait' => "required|file|mimes:{$this->mimes()}|max:" . self::MAX_SIZE_KB,
            'insurance' => "required|file|mimes:{$this->mimes()}|max:" . self::MAX_SIZE_KB,
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required' => 'Vui lòng nhập họ tên.',
            'phone.required' => 'Vui lòng nhập số điện thoại.',
            'phone.regex' => 'Số điện thoại không đúng định dạng.',
            'citizen_id.required' => 'Vui lòng nhập số CCCD.',
            'citizen_id.regex' => 'CCCD phải gồm đúng 12 chữ số.',
            'vehicle_type.required' => 'Vui lòng chọn loại xe.',
            'vehicle_name.required' => 'Vui lòng nhập tên xe.',
            'vehicle_number.required' => 'Vui lòng nhập biển số xe.',
            'vehicle_year.required' => 'Vui lòng nhập năm sản xuất.',
            'services.required' => 'Vui lòng chọn ít nhất một dịch vụ đăng ký.',
            'cccd_front.required' => 'Vui lòng tải lên CCCD mặt trước.',
            'cccd_back.required' => 'Vui lòng tải lên CCCD mặt sau.',
            'driver_license.required' => 'Vui lòng tải lên bằng lái xe.',
            'vehicle_reg.required' => 'Vui lòng tải lên giấy đăng ký xe.',
            'criminal_record.required' => 'Vui lòng tải lên lý lịch tư pháp.',
            'health_cert.required' => 'Vui lòng tải lên giấy khám sức khỏe.',
            'portrait.required' => 'Vui lòng tải lên ảnh chân dung.',
            'insurance.required' => 'Vui lòng tải lên giấy bảo hiểm.',
            '*.mimes' => 'File không đúng định dạng. Chỉ chấp nhận: JPEG, JPG, PNG, PDF.',
            '*.max' => 'File không được vượt quá 5MB.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            $this->sendValidation('Dữ liệu không hợp lệ.', $validator->errors()->toArray(), 400)
        );
    }

    private function mimes(): string
    {
        return self::ALLOWED_MIMES;
    }
}
