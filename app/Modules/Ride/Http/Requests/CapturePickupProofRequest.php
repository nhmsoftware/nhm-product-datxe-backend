<?php

declare(strict_types=1);

namespace App\Modules\Ride\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * UC-37: Validation cho yêu cầu chụp/tải ảnh xác nhận lấy hàng.
 *
 * Normal flow: Driver gửi ảnh (photo) + GPS (captured_lat, captured_lng).
 * A3/A6 flow:  Driver không chụp được — bắt buộc có skip_reason + note.
 */
class CapturePickupProofRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $hasPhoto = $this->hasFile('photo');

        return [
            // Ảnh xác nhận lấy hàng (multipart/form-data)
            'photo'        => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'], // tối đa 10MB

            // Vị trí GPS tại thời điểm chụp (milestone quan trọng — lưu DB)
            'captured_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'captured_lng' => ['nullable', 'numeric', 'between:-180,180'],

            // A3/A6: Lý do bỏ qua chụp ảnh (bắt buộc khi không có ảnh)
            'skip_reason'  => [$hasPhoto ? 'nullable' : 'required', 'string', 'in:merchant_refused,device_error,other'],
            // A3/A6: Ghi chú thêm (bắt buộc khi không có ảnh)
            'note'         => [$hasPhoto ? 'nullable' : 'required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'photo.image'          => 'Tệp tải lên phải là ảnh (JPG, PNG, WEBP).',
            'photo.mimes'          => 'Định dạng ảnh không hỗ trợ. Vui lòng dùng JPG, PNG hoặc WEBP.',
            'photo.max'            => 'Ảnh không được vượt quá 10MB.',
            'captured_lat.between' => 'Vĩ độ GPS không hợp lệ.',
            'captured_lng.between' => 'Kinh độ GPS không hợp lệ.',
            'skip_reason.required' => 'Vui lòng chọn lý do không thể chụp ảnh (A3/A6).',
            'skip_reason.in'       => 'Lý do bỏ qua không hợp lệ. Chọn: merchant_refused, device_error hoặc other.',
            'note.required'        => 'Vui lòng nhập ghi chú khi không thể chụp ảnh.',
            'note.max'             => 'Ghi chú không được vượt quá 500 ký tự.',
        ];
    }
}
