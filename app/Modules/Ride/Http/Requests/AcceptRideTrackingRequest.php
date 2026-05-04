<?php

declare(strict_types=1);

namespace App\Modules\Ride\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class AcceptRideTrackingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isDriver();
    }

    public function rules(): array
    {
        return [];
    }
}
