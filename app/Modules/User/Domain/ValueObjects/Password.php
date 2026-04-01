<?php

declare(strict_types=1);

namespace Modules\User\Domain\ValueObjects;

use Modules\User\Domain\Exceptions\InvalidPasswordException;

final class Password
{
    private readonly string $value;

    public function __construct(string $password)
    {
        if (strlen($password) < 8) {
            throw new InvalidPasswordException('Mật khẩu phải có ít nhất 8 ký tự.');
        }

        $this->value = $password;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function hash(): string
    {
        return bcrypt($this->value);
    }
}
