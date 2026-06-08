<?php

declare(strict_types=1);

namespace App\Modules\User\DTO\Admin;

use App\Modules\User\Http\Requests\Admin\CreateCustomerRequest;
use App\Modules\User\Model\Enums\Gender;

final class CreateCustomerDTO
{
    public function __construct(
        public readonly string $fullName,
        public readonly string $phone,
        public readonly ?string $email = null,
        public readonly ?Gender $gender = null,
        public readonly ?string $birthday = null,
        public readonly ?string $address = null,
        public readonly ?string $password = null,
    ) {}

    public static function fromRequest(CreateCustomerRequest $request): self
    {
        return new self(
            fullName: $request->string('full_name')->toString(),
            phone: $request->string('phone')->toString(),
            email: $request->input('email'),
            gender: $request->filled('gender')
                ? Gender::from((int) $request->input('gender'))
                : null,
            birthday: $request->input('birthday'),
            address: $request->input('address'),
            password: $request->input('password'),
        );
    }
}
