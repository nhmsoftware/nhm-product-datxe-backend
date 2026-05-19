<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Http\Requests;

use App\Core\Traits\HandleApi;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

final class GetNearbyMerchantsRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'latitude'     => ['required', 'numeric', 'between:-90,90'],
            'longitude'    => ['required', 'numeric', 'between:-180,180'],
            'radius_in_km' => ['nullable', 'numeric', 'min:0.1', 'max:100'],
            'keyword'      => ['nullable', 'string', 'max:255'],
            'page'         => ['nullable', 'integer', 'min:1'],
            'limit'        => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'latitude.required'   => 'Tọa độ vĩ độ (latitude) là bắt buộc.',
            'latitude.numeric'    => 'Vĩ độ phải là một số hợp lệ.',
            'latitude.between'    => 'Vĩ độ phải nằm trong khoảng từ -90 đến 90.',
            'longitude.required'  => 'Tọa độ kinh độ (longitude) là bắt buộc.',
            'longitude.numeric'   => 'Kinh độ phải là một số hợp lệ.',
            'longitude.between'   => 'Kinh độ phải nằm trong khoảng từ -180 đến 180.',
            'radius_in_km.numeric'=> 'Bán kính tìm kiếm phải là một số hợp lệ.',
            'radius_in_km.min'    => 'Bán kính tìm kiếm tối thiểu là 0.1 km.',
            'radius_in_km.max'    => 'Bán kính tìm kiếm tối đa là 100 km.',
            'keyword.string'      => 'Từ khóa tìm kiếm phải là chuỗi ký tự.',
            'keyword.max'         => 'Từ khóa tìm kiếm không được vượt quá 255 ký tự.',
            'page.integer'        => 'Trang hiện tại phải là một số nguyên.',
            'page.min'            => 'Trang hiện tại tối thiểu là 1.',
            'limit.integer'       => 'Số lượng phần tử trên trang phải là một số nguyên.',
            'limit.min'           => 'Số lượng phần tử tối thiểu trên trang là 1.',
            'limit.max'           => 'Số lượng phần tử tối đa trên trang là 100.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Tọa độ hoặc tham số tìm kiếm không hợp lệ.',
                'errors'  => $validator->errors(),
            ], 422)
        );
    }
}
