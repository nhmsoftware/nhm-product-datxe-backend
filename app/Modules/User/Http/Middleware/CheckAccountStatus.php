<?php

declare(strict_types=1);

namespace App\Modules\User\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAccountStatus
{
    /**
     * Handle an incoming request.
     * Kiểm tra tài khoản có bị khóa hay không trước khi xử lý request.
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Nếu không có user (chưa đăng nhập) thì bỏ qua, để authentication middleware xử lý
        if (!$user) {
            return $next($request);
        }

        // Kiểm tra tài khoản có bị khóa hay không
        if ($user->isLocked()) {
            return response()->json([
                'success' => false,
                'message' => 'Tài khoản này đã bị khóa. Vui lòng liên hệ hỗ trợ để được hỗ trợ.',
                'code'    => 'ACCOUNT_LOCKED',
            ], 403);
        }

        return $next($request);
    }
}
