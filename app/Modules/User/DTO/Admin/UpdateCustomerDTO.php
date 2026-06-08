<?php

declare(strict_types=1);

namespace App\Modules\User\DTO\Admin;

use App\Modules\User\Http\Requests\Admin\UpdateCustomerRequest;
use App\Modules\User\Model\Enums\Gender;

final class UpdateCustomerDTO
{
    public function __construct(
        public readonly string|int $userId,
        public readonly string $fullName,
        public readonly string $phone,
        public readonly ?string $email = null,
        public readonly ?Gender $gender = null,
        public readonly ?string $birthday = null,
        public readonly ?string $address = null,
        public readonly ?bool $isActive = null,
    ) {}

    public static function fromRequest(UpdateCustomerRequest $request, string|int $userId): self
    {
        return new self(
            userId: $userId,
            fullName: $request->string('full_name')->toString(),
            phone: $request->string('phone')->toString(),
            email: $request->input('email'),
            gender: $request->filled('gender')
                ? Gender::from((int) $request->input('gender'))
                : null,
            birthday: $request->input('birthday'),
            address: $request->input('address'),
            isActive: $request->has('is_active') ? $request->boolean('is_active') : null,
        );
    }
}
