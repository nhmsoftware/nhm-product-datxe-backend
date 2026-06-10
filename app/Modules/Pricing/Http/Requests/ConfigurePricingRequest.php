<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Http\Requests;

use App\Core\Traits\HandleApi;
use Illuminate\Foundation\Http\FormRequest;

final class ConfigurePricingRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vehicle_type_id'  => ['required', 'integer', 'min:1'],
            'base_price'       => ['required', 'numeric', 'min:0'],
            'distance_rate'    => ['required', 'numeric', 'gt:0'],
            'time_rate'        => ['required', 'numeric', 'gt:0'],
            'min_fare'         => ['required', 'numeric', 'gt:0', 'gte:base_price'],
            'surge_multiplier' => ['nullable', 'numeric', 'min:0'],
            'commission_rate'  => ['required', 'numeric', 'gt:0', 'max:100'],
            'is_active'        => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'vehicle_type_id.required' => 'Vui lòng chọn loại xe.',
            'vehicle_type_id.min' => 'Loại xe không hợp lệ.',
            'base_price.required'   => 'Giá mở cửa không hợp lệ.',
            'base_price.numeric'    => 'Giá mở cửa không hợp lệ.',
            'base_price.min'        => 'Giá mở cửa không được nhỏ hơn 0 đ.',
            'min_fare.required'     => 'Giá tối thiểu không hợp lệ.',
            'min_fare.numeric'      => 'Giá tối thiểu không hợp lệ.',
            'min_fare.gt'           => 'Giá tối thiểu phải lớn hơn 0 đ.',
            'min_fare.gte'          => 'Giá tối thiểu phải lớn hơn hoặc bằng giá mở cửa.',
            'distance_rate.required' => 'Giá theo kilomet không hợp lệ.',
            'distance_rate.numeric'  => 'Giá theo kilomet không hợp lệ.',
            'distance_rate.gt'       => 'Giá theo kilomet phải lớn hơn 0 đ.',
            'time_rate.required'     => 'Giá theo phút không hợp lệ.',
            'time_rate.numeric'      => 'Giá theo phút không hợp lệ.',
            'time_rate.gt'           => 'Giá theo phút phải lớn hơn 0 đ.',
            'commission_rate.required' => 'Tỷ lệ hoa hồng không hợp lệ.',
            'commission_rate.numeric'  => 'Tỷ lệ hoa hồng không hợp lệ.',
            'commission_rate.gt'       => 'Tỷ lệ hoa hồng phải lớn hơn 0%.',
            'commission_rate.max'      => 'Tỷ lệ hoa hồng không hợp lệ.',
        ];
    }
}
