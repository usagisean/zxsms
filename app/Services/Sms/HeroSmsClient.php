<?php

namespace App\Services\Sms;

use App\Models\Sms\SmsOrder;
use App\Models\Sms\SmsProviderLog;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use RuntimeException;

class HeroSmsClient
{
    /** @var Client */
    private $client;

    /** @var string */
    private $apiKey;

    /** @var string */
    private $baseUrl;

    public function __construct()
    {
        $settings = app(SmsSettingService::class);
        $this->apiKey = (string) $settings->get('herosms_api_key', config('sms.herosms.api_key'));
        $this->baseUrl = (string) $settings->get('herosms_base_url', config('sms.herosms.base_url'));
        $this->client = new Client([
            'timeout' => (int) config('sms.herosms.timeout', 15),
            'http_errors' => false,
        ]);
    }

    public function getCountries()
    {
        return $this->request('getCountries');
    }

    public function getServices($country = null, $lang = null)
    {
        return $this->request('getServicesList', [
            'country' => $country,
            'lang' => $lang ?: config('sms.herosms.lang', 'cn'),
        ]);
    }

    public function getPrices($service = null, $country = null)
    {
        return $this->request('getPrices', [
            'service' => $service,
            'country' => $country,
        ]);
    }

    public function buyNumber($service, $country, $maxPrice = null, SmsOrder $order = null)
    {
        $params = [
            'service' => $service,
            'country' => $country,
            'operator' => null,
            'maxPrice' => $maxPrice,
            'fixedPrice' => $maxPrice !== null ? '1' : null,
        ];

        $data = $this->request('getNumberV2', $params, $order);
        if ($this->isErrorPayload($data)) {
            // V2 不可用时兼容旧接口。
            $data = $this->request('getNumber', $params, $order);
        }

        return $this->parseActivation($data);
    }

    public function getStatus($activationId, SmsOrder $order = null)
    {
        $data = $this->request('getStatusV2', ['id' => $activationId], $order);
        if ($this->isErrorPayload($data)) {
            $data = $this->request('getStatus', ['id' => $activationId], $order);
        }
        return $this->parseStatus($data);
    }

    public function cancel($activationId, SmsOrder $order = null)
    {
        return $this->setStatus($activationId, 8, $order);
    }

    public function complete($activationId, SmsOrder $order = null)
    {
        return $this->setStatus($activationId, 6, $order);
    }

    public function setStatus($activationId, $status, SmsOrder $order = null)
    {
        return $this->request('setStatus', ['id' => $activationId, 'status' => $status], $order);
    }

    public function request($action, array $params = [], SmsOrder $order = null)
    {
        if ($this->apiKey === '') {
            throw new RuntimeException('HEROSMS_API_KEY 未配置');
        }

        $params = array_filter($params, function ($value) {
            return $value !== null && $value !== '';
        });
        $params = array_merge(['api_key' => $this->apiKey, 'action' => $action], $params);

        $start = microtime(true);
        $status = null;
        $body = null;
        $error = null;

        try {
            $response = $this->client->get($this->baseUrl, ['query' => $params]);
            $status = $response->getStatusCode();
            $body = (string) $response->getBody();
            $parsed = $this->parseBody($body);
            $success = $status >= 200 && $status < 300 && ! $this->isErrorPayload($parsed);
            $this->log($action, $params, $body, $status, $start, $success, null, $order);
            return $parsed;
        } catch (GuzzleException $e) {
            $error = $e->getMessage();
            $this->log($action, $params, $body, $status, $start, false, $error, $order);
            throw new RuntimeException('HeroSMS 请求失败：' . $error, 0, $e);
        }
    }

    private function parseBody($body)
    {
        $trimmed = trim((string) $body);
        if ($trimmed === '') {
            return '';
        }
        $decoded = json_decode($trimmed, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }
        return $trimmed;
    }

    public function isErrorPayload($payload)
    {
        if (is_array($payload)) {
            if (isset($payload['error']) || isset($payload['message']) && stripos((string) $payload['message'], 'error') !== false) {
                return true;
            }
            return false;
        }
        $text = strtoupper((string) $payload);
        if ($text === '') {
            return true;
        }
        $prefixes = ['BAD_KEY', 'ERROR', 'NO_BALANCE', 'NO_NUMBERS', 'WRONG_', 'BANNED', 'BAD_', 'ACCOUNT_INACTIVE'];
        foreach ($prefixes as $prefix) {
            if (strpos($text, $prefix) === 0) {
                return true;
            }
        }
        return false;
    }

    private function parseActivation($payload)
    {
        if (is_array($payload)) {
            if (isset($payload['activationId']) || isset($payload['phoneNumber'])) {
                return [
                    'activation_id' => (string) ($payload['activationId'] ?? ''),
                    'phone_number' => '+' . ltrim((string) ($payload['phoneNumber'] ?? ''), '+'),
                    'activation_cost' => isset($payload['activationCost']) ? (float) $payload['activationCost'] : null,
                    'currency' => isset($payload['currency']) ? (string) $payload['currency'] : null,
                    'raw' => $payload,
                ];
            }
        }

        if (is_string($payload) && strpos($payload, 'ACCESS_NUMBER') === 0) {
            $parts = explode(':', $payload);
            return [
                'activation_id' => isset($parts[1]) ? (string) $parts[1] : '',
                'phone_number' => isset($parts[2]) ? '+' . ltrim($parts[2], '+') : '',
                'activation_cost' => null,
                'currency' => null,
                'raw' => $payload,
            ];
        }

        throw new RuntimeException('HeroSMS 购买号码失败：' . (is_scalar($payload) ? $payload : json_encode($payload, JSON_UNESCAPED_UNICODE)));
    }

    private function parseStatus($payload)
    {
        if (is_array($payload)) {
            $sms = isset($payload['sms']) && is_array($payload['sms']) ? $payload['sms'] : null;
            $call = isset($payload['call']) && is_array($payload['call']) ? $payload['call'] : null;
            $message = $sms ?: $call;
            return [
                'has_message' => (bool) ($message && (! empty($message['code']) || ! empty($message['text']))),
                'cancelled' => ! empty($payload['status']) && in_array(strtoupper((string) $payload['status']), ['STATUS_CANCEL', 'CANCEL', 'CANCELLED', 'ACCESS_CANCEL'], true),
                'type' => $sms ? 'sms' : ($call ? 'call' : 'sms'),
                'code' => $message['code'] ?? null,
                'text' => $message['text'] ?? null,
                'raw' => $payload,
            ];
        }

        if (is_string($payload)) {
            if (strpos($payload, 'STATUS_OK:') === 0) {
                $code = substr($payload, strlen('STATUS_OK:'));
                return ['has_message' => true, 'cancelled' => false, 'type' => 'sms', 'code' => $code, 'text' => null, 'raw' => $payload];
            }
            return ['has_message' => false, 'cancelled' => in_array(strtoupper($payload), ['STATUS_CANCEL', 'ACCESS_CANCEL', 'CANCEL'], true), 'type' => 'sms', 'code' => null, 'text' => null, 'raw' => $payload];
        }

        return ['has_message' => false, 'cancelled' => false, 'type' => 'sms', 'code' => null, 'text' => null, 'raw' => $payload];
    }

    private function log($action, array $params, $body, $status, $start, $success, $error, SmsOrder $order = null)
    {
        try {
            $safeParams = $params;
            if (isset($safeParams['api_key'])) {
                $safeParams['api_key'] = $this->redact($safeParams['api_key']);
            }

            SmsProviderLog::create([
                'sms_order_id' => $order ? $order->id : null,
                'provider' => 'herosms',
                'action' => $action,
                'method' => 'GET',
                'url' => $this->baseUrl,
                'request_payload' => $safeParams,
                'response_body' => is_string($body) ? mb_substr($body, 0, 5000) : null,
                'http_status' => $status,
                'duration_ms' => (int) round((microtime(true) - $start) * 1000),
                'is_success' => (bool) $success,
                'error_message' => $error,
            ]);
        } catch (\Throwable $e) {
            // 日志失败不能影响主流程。
        }
    }

    private function redact($value)
    {
        $value = (string) $value;
        if (strlen($value) <= 8) {
            return '***';
        }
        return substr($value, 0, 4) . '***' . substr($value, -4);
    }
}
