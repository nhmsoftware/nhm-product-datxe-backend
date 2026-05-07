<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use App\Modules\User\Http\Middleware\CheckAccountStatus;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Cấu hình cho phép Frontend gọi API (CORS)
        $middleware->statefulApi();
        
        // Đăng ký middleware kiểm tra trạng thái tài khoản toàn cục
        $middleware->alias([
            'check.account.status' => CheckAccountStatus::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn chưa đăng nhập hoặc phiên đăng nhập đã hết hạn.',
            ], 401);
        });
    })
    ->create();
