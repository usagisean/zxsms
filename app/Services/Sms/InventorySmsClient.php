<?php

namespace App\Services\Sms;

use App\Models\Sms\SmsInventoryCard;
use App\Models\Sms\SmsOrder;
use App\Models\Sms\SmsProviderLog;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use RuntimeException;

class InventorySmsClient
{
    /** @var Client */
    private $client;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => (int) config('sms.us62.timeout', 15),
            'http_errors' => false,
        ]);
    }

    public function getMessage(SmsInventoryCard $card, SmsOrder $order = null)
    {
        $url = trim((string) $card->sms_url);
        if ($url === '') {
            throw new RuntimeException('库存取码链接为空');
        }

        $start = microtime(true);
        $status = null;
        $body = null;
        $error = null;

        try {
            $response = $this->client->get($url);
            $status = $response->getStatusCode();
            $body = (string) $response->getBody();
            $parsed = $this->parseBody($body);
            $result = $this->normalizeMessage($parsed);
            $this->log($card, $order, $body, $status, $start, $status >= 200 && $status < 300, null);
            return $result;
        } catch (GuzzleException $e) {
            $error = $e->getMessage();
            $this->log($card, $order, $body, $status, $start, false, $error);
            throw new RuntimeException('取码请求失败：' . $error, 0, $e);
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

    private function normalizeMessage($payload)
    {
        $text = $this->extractText($payload);
        $rawText = trim((string) $text);
        $code = $this->extractCode($rawText);
        $hasMessage = $rawText !== '' && ! $this->looksLikeWaitingText($rawText);

        return [
            'has_message' => $hasMessage,
            'cancelled' => false,
            'type' => 'sms',
            'code' => $code,
            'text' => $hasMessage ? $rawText : null,
            'raw' => $payload,
        ];
    }

    private function extractText($payload)
    {
        if (is_string($payload) || is_numeric($payload)) {
            return (string) $payload;
        }
        if (! is_array($payload)) {
            return '';
        }

        foreach (['sms_text', 'sms_content', 'sms', 'text', 'content', 'body'] as $key) {
            if (isset($payload[$key]) && is_scalar($payload[$key])) {
                return (string) $payload[$key];
            }
        }

        foreach (['data', 'result', 'list', 'items', 'rows'] as $key) {
            if (! isset($payload[$key])) {
                continue;
            }
            $value = $payload[$key];
            if (is_scalar($value)) {
                return (string) $value;
            }
            if (is_array($value)) {
                if ($this->isList($value)) {
                    for ($i = count($value) - 1; $i >= 0; $i--) {
                        $nested = $this->extractText($value[$i]);
                        if (trim($nested) !== '') {
                            return $nested;
                        }
                    }
                }
                $nested = $this->extractText($value);
                if (trim($nested) !== '') {
                    return $nested;
                }
            }
        }

        foreach (['message', 'msg'] as $key) {
            if (isset($payload[$key]) && is_scalar($payload[$key])) {
                return (string) $payload[$key];
            }
        }

        if (isset($payload['code']) && is_scalar($payload['code']) && preg_match('/^\d{4,8}$/', (string) $payload['code'])) {
            return (string) $payload['code'];
        }

        return '';
    }

    private function isList(array $array)
    {
        if ($array === []) {
            return true;
        }
        return array_keys($array) === range(0, count($array) - 1);
    }

    private function looksLikeWaitingText($text)
    {
        $text = trim((string) $text);
        if ($text === '' || $text === '[]' || $text === '{}' || strtolower($text) === 'null') {
            return true;
        }
        $lower = mb_strtolower($text);
        if (in_array($lower, ['ok', 'success', 'true', 'false'], true)) {
            return true;
        }
        foreach (['暂无', '等待', '未收到', '未查询到', '没有短信', '无短信', '无数据', 'no sms', 'not found', 'empty', 'pending', 'waiting'] as $needle) {
            if (mb_strpos($lower, $needle) !== false) {
                return true;
            }
        }
        return false;
    }

    private function extractCode($text)
    {
        $text = (string) $text;
        if (preg_match('/(?<!\d)(\d{4,8})(?!\d)/u', $text, $matches)) {
            return $matches[1];
        }
        if (preg_match('/code[^0-9]{0,12}(\d{4,8})/iu', $text, $matches)) {
            return $matches[1];
        }
        return null;
    }

    private function log(SmsInventoryCard $card, SmsOrder $order = null, $body = null, $status = null, $start = null, $success = false, $error = null)
    {
        try {
            SmsProviderLog::create([
                'sms_order_id' => $order ? $order->id : null,
                'provider' => 'inventory',
                'action' => 'fetch_sms_url',
                'method' => 'GET',
                'url' => 'inventory-card:' . $card->id,
                'request_payload' => ['card_id' => $card->id, 'phone' => $card->phone_number],
                'response_body' => is_string($body) ? mb_substr($body, 0, 5000) : null,
                'http_status' => $status,
                'duration_ms' => $start ? (int) round((microtime(true) - $start) * 1000) : null,
                'is_success' => (bool) $success,
                'error_message' => $error,
            ]);
        } catch (\Throwable $e) {
            // 日志失败不能影响取码。
        }
    }
}
