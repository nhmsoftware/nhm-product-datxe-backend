<?php

declare(strict_types=1);

namespace App\Modules\Ride\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateDriverLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isDriver();
    }

    public function rules(): array
    {
        return [
            'lat'        => 'required|numeric|between:-90,90',
            'lng'        => 'required|numeric|between:-180,180',
            'heading'    => 'nullable|numeric|between:0,360',
            'speed'      => 'nullable|numeric|min:0',
            'accuracy'   => 'nullable|numeric|min:0',
            'tracked_at' => 'nullable|date',
        ];
    }
}
