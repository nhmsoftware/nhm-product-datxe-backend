<?php

declare(strict_types=1);

namespace Modules\User\Presentation\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\User\Domain\Exceptions\AuthenticationFailedException;
use Modules\User\Domain\Exceptions\InvalidPasswordException;
use Modules\User\Domain\Exceptions\InvalidPhoneException;
use Modules\User\Domain\Exceptions\OtpExpiredException;
use Modules\User\Domain\Exceptions\OtpInvalidException;
use Modules\User\Domain\Exceptions\OtpTooManyAttemptsException;
use Modules\User\Domain\Exceptions\UserAlreadyExistsException;
use Throwable;

/**
 * Đăng ký class này vào app/Exceptions/Handler.php:
 *
 *   public function register(): void
 *   {
 *       $this->renderable(
 *           fn(Throwable $e, Request $r) =>
 *               (new UserModuleExceptionHandler())->handle($e, $r)
 *       );
 *   }
 */
class UserModuleExceptionHandler
{
    public function handle(Throwable $e, Request $request): ?JsonResponse
    {
        // Chỉ xử lý JSON requests
        if (! $request->expectsJson()) {
            return null;
        }

        return match (true) {
            // ── 409 Conflict ───────────────────────────────────
            $e instanceof UserAlreadyExistsException
                => $this->error($e->getMessage(), 409, 'USER_ALREADY_EXISTS'),

            // ── 401 Unauthorized ───────────────────────────────
            $e instanceof AuthenticationFailedException
                => $this->error($e->getMessage(), 401, 'AUTH_FAILED'),

            $e instanceof AuthenticationException
                => $this->error('Bạn cần đăng nhập để tiếp tục.', 401, 'UNAUTHENTICATED'),

            // ── 400 Bad Request ────────────────────────────────
            $e instanceof OtpExpiredException
                => $this->error($e->getMessage(), 400, 'OTP_EXPIRED'),

            $e instanceof OtpInvalidException
                => $this->error($e->getMessage(), 400, 'OTP_INVALID'),

            $e instanceof OtpTooManyAttemptsException
                => $this->error($e->getMessage(), 429, 'OTP_TOO_MANY_ATTEMPTS'),

            $e instanceof InvalidPhoneException
                => $this->error($e->getMessage(), 422, 'INVALID_PHONE'),

            $e instanceof InvalidPasswordException
                => $this->error($e->getMessage(), 422, 'INVALID_PASSWORD'),

            // ── 422 Validation ─────────────────────────────────
            $e instanceof ValidationException
                => response()->json([
                    'message' => 'Dữ liệu không hợp lệ.',
                    'code'    => 'VALIDATION_ERROR',
                    'errors'  => $e->errors(),
                ], 422),

            default => null, // Để Laravel xử lý các exception khác
        };
    }

    private function error(string $message, int $status, string $code): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'code'    => $code,
        ], $status);
    }
}
