<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\Building;

class TuyaApiService
{
    private Client $client;
    private Building $building;
    private string $baseUrl;
    private string $clientId;
    private string $clientSecret;

    public function __construct(Building $building)
    {
        $this->building = $building;
        $this->baseUrl = config('tuya.base_url');
        $this->clientId = $building->tuya_client_id;
        $this->clientSecret = $building->tuya_client_secret;

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => config('tuya.timeout', 30),
        ]);
    }

    /**
     * Get or refresh access token
     */
    public function getAccessToken(bool $forceRefresh = false): string
    {
        $cacheKey = "tuya_token_{$this->building->id}";

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        // Reduced cache time to 3600s (1h) to be safer than 7000s (almost 2h)
        return Cache::remember($cacheKey, now()->addSeconds(3600), function () {
            $timestamp = (string) (time() * 1000);
            // Try complex signature for token too (v2.0 requirement for new projects)
            $sign = $this->generateSign('GET', '/v1.0/token?grant_type=1', [], $timestamp);

            $response = $this->client->get('/v1.0/token', [
                'query' => ['grant_type' => 1],
                'headers' => [
                    'client_id' => $this->clientId,
                    'sign' => $sign,
                    't' => $timestamp,
                    'sign_method' => 'HMAC-SHA256',
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            if (!$data['success'] ?? false) {
                throw new \Exception('Failed to get Tuya access token: ' . ($data['msg'] ?? 'Unknown error'));
            }

            return $data['result']['access_token'];
        });
    }

    /**
     * Generate HMAC-SHA256 signature for Tuya API (v1.0/v2.0 Standard)
     */
    private function generateSign(string $method, string $path, array $body = [], string $timestamp = null, string $accessToken = ''): string
    {
        $timestamp = $timestamp ?? (string) (time() * 1000);

        // 1. Content-SHA256
        $contentHash = hash('sha256', empty($body) ? '' : json_encode($body, JSON_UNESCAPED_UNICODE));

        // 2. StringToSign
        // Method + \n + Content-SHA256 + \n + Headers + \n + Url
        // We only use standard headers no custom ones to simplify
        $stringToSign = implode("\n", [
            strtoupper($method),
            $contentHash,
            '', // Headers are empty for now
            $path
        ]);

        // 3. Final String
        // client_id + access_token + t + nonce + stringToSign
        // Nonce is optional/empty
        $str = $this->clientId . $accessToken . $timestamp . $stringToSign;

        return strtoupper(hash_hmac('sha256', $str, $this->clientSecret));
    }

    // Note: Token request signature is different (SIMPLE MODE)
    // client_id + t (signed with secret)
    private function generateTokenSign(string $timestamp): string
    {
        $str = $this->clientId . $timestamp;
        return strtoupper(hash_hmac('sha256', $str, $this->clientSecret));
    }

    /**
     * Get password ticket for encryption
     */
    public function getPasswordTicket(string $deviceId): array
    {
        $accessToken = $this->getAccessToken();
        $timestamp = (string) (time() * 1000);
        $path = "/v1.0/devices/{$deviceId}/door-lock/password-ticket";
        $sign = $this->generateSign('POST', $path, [], $timestamp, $accessToken);

        $response = $this->client->post($path, [
            'headers' => [
                'client_id' => $this->clientId,
                'access_token' => $accessToken,
                'sign' => $sign,
                't' => $timestamp,
                'sign_method' => 'HMAC-SHA256',
                'Content-Type' => 'application/json',
            ],
        ]);

        $data = json_decode($response->getBody(), true);

        if (!$data['success'] ?? false) {
            throw new \Exception('Failed to get password ticket: ' . ($data['msg'] ?? 'Unknown error'));
        }

        return $data['result']; // ['ticket_id', 'ticket_key', 'expire_time']
    }

    /**
     * Encrypt password using AES with ticket
     */
    public function encryptPassword(string $password, string $ticketKey): string
    {
        // Logic from Python script (X7 compatible):
        // 1. ticket_bytes = unhexlify(ticket_key_hex)
        // 2. derived = AES-ECB-DECRYPT(ticket_bytes, access_secret)
        // 3. key16 = derived[:16]
        // 4. password_hex = AES-ECB-ENCRYPT(pin, key16) with PKCS7 padding

        // Clean ticket key
        $key = str_replace('0x', '', $ticketKey);
        $ticketBytes = hex2bin($key);

        $accessSecret = $this->clientSecret;

        // Determine AES mode for decrypting ticket key based on secret length
        $paramLen = strlen($accessSecret);
        if ($paramLen === 16) {
            $algo = 'AES-128-ECB';
        } elseif ($paramLen === 24) {
            $algo = 'AES-192-ECB';
        } elseif ($paramLen === 32) {
            $algo = 'AES-256-ECB';
        } else {
            // Fallback to AES-256 (standard Tuya secret length is usually 32 chars)
            $algo = 'AES-256-ECB';
        }

        // Step 1: Decrypt ticket key using Client Secret
        $derived = openssl_decrypt(
            $ticketBytes,
            $algo,
            $accessSecret,
            OPENSSL_RAW_DATA | OPENSSL_NO_PADDING
        );

        if ($derived === false) {
            throw new \Exception('Failed to decrypt ticket key');
        }

        // Step 2: Take first 16 bytes for the PIN encryption key
        $key16 = substr($derived, 0, 16);

        // Step 3: Encrypt PIN using derived key (AES-128-ECB)
        // openssl_encrypt uses PKCS7 padding by default
        $encrypted = openssl_encrypt(
            $password,
            'AES-128-ECB',
            $key16,
            OPENSSL_RAW_DATA
        );

        return strtoupper(bin2hex($encrypted));
    }

    /**
     * Create temporary password/PIN
     */
    public function createTempPassword(string $deviceId, string $pin, int $effectiveTime, int $invalidTime, string $name): array
    {
        // Get ticket
        $ticket = $this->getPasswordTicket($deviceId);

        // Encrypt PIN using the special derived logic
        $encryptedPin = $this->encryptPassword($pin, $ticket['ticket_key']);

        // Prepare request
        $accessToken = $this->getAccessToken();
        $timestamp = (string) (time() * 1000);
        $path = "/v1.0/devices/{$deviceId}/door-lock/temp-password";  // Changed to v1.0

        $body = [
            'name' => $name,
            'password' => $encryptedPin,
            'effective_time' => $effectiveTime,
            'invalid_time' => $invalidTime,
            'password_type' => 'ticket',
            'ticket_id' => $ticket['ticket_id'],
        ];

        // Send request
        $sign = $this->generateSign('POST', $path, $body, $timestamp, $accessToken);

        $response = $this->client->post($path, [
            'headers' => [
                'client_id' => $this->clientId,
                'access_token' => $accessToken,
                'sign' => $sign,
                't' => $timestamp,
                'sign_method' => 'HMAC-SHA256',
                // Content-Type is added by Guzzle when using 'json'
            ],
            'json' => $body,
        ]);

        $data = json_decode($response->getBody(), true);

        if (!$data['success'] ?? false) {
            throw new \Exception('Failed to create temp password: ' . ($data['msg'] ?? 'Unknown error'));
        }

        return $data['result']; // ['id' => password_id]
    }

    /**
     * Get temporary password details
     */
    public function getTempPassword(string $deviceId, string $passwordId): array
    {
        $accessToken = $this->getAccessToken();
        $timestamp = (string) (time() * 1000);
        $path = "/v2.0/devices/{$deviceId}/door-lock/temp-passwords/{$passwordId}";
        $sign = $this->generateSign('GET', $path, [], $timestamp, $accessToken);

        $response = $this->client->get($path, [
            'headers' => [
                'client_id' => $this->clientId,
                'access_token' => $accessToken,
                'sign' => $sign,
                't' => $timestamp,
                'sign_method' => 'HMAC-SHA256',
            ],
        ]);

        $data = json_decode($response->getBody(), true);

        if (!$data['success'] ?? false) {
            throw new \Exception('Failed to get temp password: ' . ($data['msg'] ?? 'Unknown error'));
        }

        return $data['result'];
    }

    /**
     * Get device status (Battery, Online, etc.)
     */
    public function getDeviceStatus(string $deviceId): array
    {
        $accessToken = $this->getAccessToken();
        $timestamp = (string) (time() * 1000);
        $path = "/v1.0/iot-03/devices/{$deviceId}/status";

        $sign = $this->generateSign('GET', $path, [], $timestamp, $accessToken);

        $response = $this->client->get($path, [
            'headers' => [
                'client_id' => $this->clientId,
                'access_token' => $accessToken,
                'sign' => $sign,
                't' => $timestamp,
                'sign_method' => 'HMAC-SHA256',
            ],
        ]);

        $data = json_decode($response->getBody(), true);

        if (!$data['success'] ?? false) {
            throw new \Exception('Failed to get device status: ' . ($data['msg'] ?? 'Unknown error'));
        }

        return $data['result'] ?? [];
    }

    /**
     * Get list of temporary passwords
     */
    public function getTempPasswords(string $deviceId, int $pageNo = 1, int $pageSize = 20): array
    {
        try {
            return $this->executeGetTempPasswords($deviceId, $pageNo, $pageSize);
        } catch (\Exception $e) {
            // Check for token invalid error (often code 1010 or specific msg)
            // Tuya error messages vary, checking for "token invalid" or "permission denied"
            if (
                str_contains(strtolower($e->getMessage()), 'token invalid') ||
                str_contains(strtolower($e->getMessage()), 'permission denied')
            ) {

                Log::warning("Tuya API Token Invalid. Refreshing and retrying...");

                // Force refresh token
                $this->getAccessToken(true);

                // Retry once
                return $this->executeGetTempPasswords($deviceId, $pageNo, $pageSize);
            }
            throw $e;
        }
    }

    private function executeGetTempPasswords(string $deviceId, int $pageNo, int $pageSize): array
    {
        $accessToken = $this->getAccessToken();
        $timestamp = (string) (time() * 1000);
        $path = "/v1.0/smart-lock/devices/{$deviceId}/stand-by-lock-temp-passwords";

        $query = http_build_query([
            'page_no' => $pageNo,
            'page_size' => $pageSize,
            'valid' => 'true',
        ]);

        $sign = $this->generateSign('GET', $path . '?' . $query, [], $timestamp, $accessToken);

        $response = $this->client->get($path, [
            'query' => [
                'page_no' => $pageNo,
                'page_size' => $pageSize,
                'valid' => 'true',
            ],
            'headers' => [
                'client_id' => $this->clientId,
                'access_token' => $accessToken,
                'sign' => $sign,
                't' => $timestamp,
                'sign_method' => 'HMAC-SHA256',
            ],
        ]);

        $data = json_decode($response->getBody(), true);

        if (!$data['success'] ?? false) {
            throw new \Exception('Failed to get temp passwords: ' . ($data['msg'] ?? 'Unknown error'));
        }

        $records = $data['result']['records'] ?? [];
        $list = array_map(function ($item) {
            return [
                'id' => $item['password_id'] ?? $item['id'] ?? null,
                'name' => $item['name'] ?? '',
                'password' => $item['password'] ?? null,
                'effective_time' => $item['effective_time'] ?? 0,
                'invalid_time' => $item['expired_time'] ?? $item['invalid_time'] ?? 0,
                'status' => $item['effective_flag'] ?? 1,
                'delivery_status' => $item['delivery_status'] ?? '',
            ];
        }, $records);

        return ['list' => $list, 'total' => $data['result']['total'] ?? count($list)];
    }

    /**
     * Update temporary password validity/name
     */
    public function updateTempPassword(string $deviceId, string $passwordId, string $pin, ?string $name = null, ?int $effectiveTime = null, ?int $invalidTime = null): bool
    {
        // 1. Get new ticket (REQUIRED for update)
        $ticket = $this->getPasswordTicket($deviceId);

        // 2. Encrypt PIN using the new ticket key
        $encryptedPin = $this->encryptPassword($pin, $ticket['ticket_key']);

        $accessToken = $this->getAccessToken();
        $timestamp = (string) (time() * 1000);
        // User provided endpoint was singular but failed with 'uri path invalid'.
        // Added /modify-password suffix as per Tuya API documentation
        $path = "/v1.0/devices/{$deviceId}/door-lock/temp-passwords/{$passwordId}/modify-password";

        $body = [
            'password' => $encryptedPin,
            'password_type' => 'ticket',
            'ticket_id' => $ticket['ticket_id'],
        ];

        if ($name)
            $body['name'] = $name;
        if ($effectiveTime)
            $body['effective_time'] = $effectiveTime;
        if ($invalidTime)
            $body['invalid_time'] = $invalidTime;

        $sign = $this->generateSign('PUT', $path, $body, $timestamp, $accessToken);

        $response = $this->client->put($path, [
            'headers' => [
                'client_id' => $this->clientId,
                'access_token' => $accessToken,
                'sign' => $sign,
                't' => $timestamp,
                'sign_method' => 'HMAC-SHA256',
                'Content-Type' => 'application/json',
            ],
            'json' => $body,
        ]);

        $data = json_decode($response->getBody(), true);

        if (!$data['success'] ?? false) {
            throw new \Exception('Failed to update temp password: ' . ($data['msg'] ?? 'Unknown error'));
        }

        return true;
    }

    /**
     * Delete temporary password
     */
    public function deleteTempPassword(string $deviceId, string $passwordId): bool
    {
        $accessToken = $this->getAccessToken();
        $timestamp = (string) (time() * 1000);

        $path = "/v1.0/devices/{$deviceId}/door-lock/temp-passwords/{$passwordId}";

        // Don't send body for DELETE to match signature of empty string
        $sign = $this->generateSign('DELETE', $path, [], $timestamp, $accessToken);

        try {
            $response = $this->client->delete($path, [
                'headers' => [
                    'client_id' => $this->clientId,
                    'access_token' => $accessToken,
                    'sign' => $sign,
                    't' => $timestamp,
                    'sign_method' => 'HMAC-SHA256',
                    // 'Content-Type' => 'application/json', // Not needed if no body
                ],
                // 'json' => [], // REMOVED to avoid sending '[]'
            ]);

            $data = json_decode($response->getBody(), true);

            if (!$data['success'] ?? false) {
                throw new \Exception('Failed to delete temp password: ' . ($data['msg'] ?? 'Unknown error'));
            }

            return true;

        } catch (\Exception $e) {
            // If error is "password has expired", consider it successful
            if (str_contains($e->getMessage(), '2304') || str_contains($e->getMessage(), 'expired')) {
                Log::info("Password already expired, considering deletion successful");
                return true;
            }
            throw $e;
        }
    }

    /**
     * Get unlock records/logs
     */
    public function getUnlockRecords(string $deviceId, int $startTime, int $endTime, int $pageNo = 1, int $pageSize = 50): array
    {
        $accessToken = $this->getAccessToken();
        $timestamp = (string) (time() * 1000);
        $path = "/v1.0/devices/{$deviceId}/door-lock/unlock-records";

        // IMPORTANT: start_time and end_time must be in MILLISECONDS
        $query = http_build_query([
            'page_no' => $pageNo,
            'page_size' => $pageSize,
            'start_time' => $startTime * 1000, // Convert to ms
            'end_time' => $endTime * 1000,     // Convert to ms
        ]);

        $sign = $this->generateSign('GET', $path . '?' . $query, [], $timestamp, $accessToken);

        $response = $this->client->get($path, [
            'query' => [
                'page_no' => $pageNo,
                'page_size' => $pageSize,
                'start_time' => $startTime * 1000,
                'end_time' => $endTime * 1000,
            ],
            'headers' => [
                'client_id' => $this->clientId,
                'access_token' => $accessToken,
                'sign' => $sign,
                't' => $timestamp,
                'sign_method' => 'HMAC-SHA256',
            ],
        ]);

        $data = json_decode($response->getBody(), true);

        if (!$data['success'] ?? false) {
            throw new \Exception('Failed to get unlock records: ' . ($data['msg'] ?? 'Unknown error'));
        }

        return $data['result']; // ['logs' => [...], 'total' => X]
    }

    /**
     * Get alert records
     */
    public function getAlertRecords(string $deviceId, array $codes = ['doorbell', 'alarm_lock', 'hijack'], int $pageNo = 1, int $pageSize = 50): array
    {
        $accessToken = $this->getAccessToken();
        $timestamp = (string) (time() * 1000);
        $path = "/v1.0/devices/{$deviceId}/door-lock/alert-records";

        $query = http_build_query([
            'page_no' => $pageNo,
            'page_size' => $pageSize,
            'codes' => implode(',', $codes),
        ]);

        $sign = $this->generateSign('GET', $path . '?' . $query, [], $timestamp, $accessToken);

        $response = $this->client->get($path, [
            'query' => [
                'page_no' => $pageNo,
                'page_size' => $pageSize,
                'codes' => implode(',', $codes),
            ],
            'headers' => [
                'client_id' => $this->clientId,
                'access_token' => $accessToken,
                'sign' => $sign,
                't' => $timestamp,
                'sign_method' => 'HMAC-SHA256',
            ],
        ]);

        $data = json_decode($response->getBody(), true);

        if (!$data['success'] ?? false) {
            throw new \Exception('Failed to get alert records: ' . ($data['msg'] ?? 'Unknown error'));
        }

        return $data['result'] ?? ['logs' => [], 'total' => 0];
    }

    public function rawRequest(string $method, string $path, array $body = [])
    {
        $accessToken = $this->getAccessToken();
        $timestamp = (string) (time() * 1000);
        $sign = $this->generateSign($method, $path, $body, $timestamp, $accessToken);

        try {
            $options = [
                'headers' => [
                    'client_id' => $this->clientId,
                    'access_token' => $accessToken,
                    'sign' => $sign,
                    't' => $timestamp,
                    'sign_method' => 'HMAC-SHA256',
                    'Content-Type' => 'application/json',
                ],
            ];

            if (!empty($body)) {
                $options['json'] = $body;
            }

            $response = $this->client->request($method, $path, $options);
            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            return ['success' => false, 'msg' => $e->getMessage()];
        }
    }
}
