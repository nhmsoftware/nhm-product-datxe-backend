<?php

declare(strict_types=1);

namespace App\Modules\Notification\Services;

use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FirebaseCloudMessagingService
{
    private const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    /**
     * Gửi thông báo qua FCM HTTP v1 API
     */
    public function send(string $token, string $title, string $body, array $data = []): bool
    {
        $credentialsPath = storage_path('app/firebase/credentials.json');
        if (!file_exists($credentialsPath)) {
            Log::warning('FCM Service Account file not found at ' . $credentialsPath . '. Skipping push.');
            return false;
        }

        $credentials = json_decode(file_get_contents($credentialsPath), true);
        $projectId = $credentials['project_id'] ?? null;

        if (!$projectId) {
            Log::error('Invalid FCM Service Account file: Missing project_id.');
            return false;
        }

        $accessToken = $this->getAccessToken($credentials);
        if (!$accessToken) {
            return false;
        }

        $fcmEndpoint = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        // Chuẩn hóa data: FCM chỉ nhận string cho các value trong data object
        $normalizedData = [];
        foreach ($data as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $normalizedData[$key] = json_encode($value);
            } else {
                $normalizedData[$key] = (string) $value;
            }
        }

        $payload = [
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => empty($normalizedData) ? null : $normalizedData,
                'android' => [
                    'priority' => 'high',
                ],
                'apns' => [
                    'payload' => [
                        'aps' => [
                            'content-available' => 1,
                            'sound' => 'default',
                        ],
                    ],
                ],
            ]
        ];

        // Remove null data field if empty
        if (empty($payload['message']['data'])) {
            unset($payload['message']['data']);
        }

        $response = Http::withToken($accessToken)->post($fcmEndpoint, $payload);

        if ($response->successful()) {
            return true;
        }

        Log::error('Failed to send FCM message', [
            'status' => $response->status(),
            'body' => $response->body(),
            'token' => $token
        ]);

        return false;
    }

    /**
     * Sinh hoặc lấy Access Token từ Cache
     */
    private function getAccessToken(array $credentials): ?string
    {
        return Cache::remember('fcm_access_token', 55 * 60, function () use ($credentials) {
            $now = time();
            $payload = [
                'iss' => $credentials['client_email'],
                'scope' => self::SCOPE,
                'aud' => self::TOKEN_URL,
                'iat' => $now,
                'exp' => $now + 3600,
            ];

            try {
                $jwt = JWT::encode($payload, $credentials['private_key'], 'RS256');

                $response = Http::asForm()->post(self::TOKEN_URL, [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt,
                ]);

                if ($response->successful()) {
                    return $response->json('access_token');
                }

                Log::error('Failed to generate FCM access token', ['response' => $response->body()]);
                return null;
            } catch (\Exception $e) {
                Log::error('Exception generating FCM access token: ' . $e->getMessage());
                return null;
            }
        });
    }
}
