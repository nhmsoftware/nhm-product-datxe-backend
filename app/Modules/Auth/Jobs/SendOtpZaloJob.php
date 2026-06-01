<?php

declare(strict_types=1);

namespace App\Modules\Auth\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendOtpZaloJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly string $phone,
        public readonly string $otpCode
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $accessToken = config('services.zalo.access_token');
        $templateId = config('services.zalo.template_id');

        if (empty($accessToken) || empty($templateId)) {
            Log::warning('Zalo ZNS: Access token or Template ID is not configured. Cannot send OTP.');
            return;
        }

        // Định dạng lại số điện thoại: thay thế số 0 ở đầu bằng 84 (chuẩn bắt buộc của Zalo ZNS)
        $formattedPhone = $this->phone;
        if (str_starts_with($formattedPhone, '0')) {
            $formattedPhone = '84' . substr($formattedPhone, 1);
        }

        $url = 'https://business.openapi.zalo.me/message/template';

        $response = Http::withHeaders([
            'access_token' => $accessToken,
        ])
        ->timeout(10)
        ->post($url, [
            'phone' => $formattedPhone,
            'template_id' => $templateId,
            'template_data' => [
                'otp' => $this->otpCode, // Tên biến 'otp' này phải khớp với tham số trong Mẫu ZNS bạn đã đăng ký
            ],
            'tracking_id' => 'otp_' . uniqid(),
        ]);

        if ($response->failed() || $response->json('error') !== 0) {
            Log::error('Zalo ZNS Error', [
                'phone' => $this->phone,
                'formatted_phone' => $formattedPhone,
                'response' => $response->json(),
                'status_code' => $response->status(),
            ]);
        } else {
            Log::info('Zalo ZNS Sent', [
                'phone' => $this->phone,
                'response' => $response->json(),
            ]);
        }
    }
}
