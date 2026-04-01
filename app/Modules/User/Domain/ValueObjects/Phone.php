<?php

declare(strict_types=1);

namespace Modules\User\Domain\ValueObjects;

use Modules\User\Domain\Exceptions\InvalidPhoneException;

final class Phone
{
    private readonly string $value;

    public function __construct(string $phone)
    {
        $normalized = $this->normalize($phone);

        if (! $this->isValid($normalized)) {
            throw new InvalidPhoneException("Số điện thoại không hợp lệ: {$phone}");
        }

        $this->value = $normalized;
    }

    private function normalize(string $phone): string
    {
        // Remove spaces, dashes
        $phone = preg_replace('/[\s\-]/', '', $phone);

        // Convert +84 → 0
        if (str_starts_with($phone, '+84')) {
            $phone = '0' . substr($phone, 3);
        }

        return $phone;
    }

    private function isValid(string $phone): bool
    {
        // Vietnamese phone: 10 digits starting with 0
        return (bool) preg_match('/^0[3-9]\d{8}$/', $phone);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
