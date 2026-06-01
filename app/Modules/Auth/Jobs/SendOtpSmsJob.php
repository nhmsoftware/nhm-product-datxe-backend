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

class SendOtpSmsJob implements ShouldQueue
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
        $accessToken = config('services.speedsms.access_token');
        $sender = config('services.speedsms.sender', 'SPEEDSMS');

        if (empty($accessToken)) {
            Log::warning('SpeedSMS: Access token is not configured. Cannot send OTP.');
            return;
        }

        $url = 'https://api.speedsms.vn/index.php/sms/send';
        $content = "Ma xac thuc cua ban la: {$this->otpCode}. Ma co hieu luc trong 10 phut.";

        // SpeedSMS sử dụng Basic Auth với username là Access Token, password là chuỗi bất kỳ (ở đây dùng 'x')
        $response = Http::withBasicAuth($accessToken, 'x')
            ->timeout(10)
            ->post($url, [
                'to' => [$this->phone],
                'content' => $content,
                'sms_type' => 2, // 2 = SMS CSKH/OTP, yêu cầu phải đăng ký mẫu hoặc dùng mẫu mặc định nếu được cấp phép. Nếu dùng type 4 (SMS gửi bằng đầu số ngẫu nhiên) thì không cần đăng ký nhưng có thể bị chặn. Khuyến nghị type 2.
                'sender' => $sender,
            ]);

        if ($response->failed() || $response->json('status') !== 'success') {
            Log::error('SpeedSMS Error', [
                'phone' => $this->phone,
                'response' => $response->json(),
                'status_code' => $response->status(),
            ]);
        } else {
            Log::info('SpeedSMS Sent', [
                'phone' => $this->phone,
                'response' => $response->json(),
            ]);
        }
    }
}
