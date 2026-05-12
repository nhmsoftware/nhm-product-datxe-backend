<?php

declare(strict_types=1);

namespace App\Modules\Complaint\Http\Requests;

use App\Core\Traits\HandleApi;
use App\Modules\Complaint\Model\Enums\ComplaintResolutionAction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class HandleComplaintRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true; // Admin role check should be in middleware
    }

    public function rules(): array
    {
        return [
            'action' => ['required', new Enum(ComplaintResolutionAction::class)],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
