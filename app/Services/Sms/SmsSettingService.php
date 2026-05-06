<?php

namespace App\Services\Sms;

use App\Models\Sms\SmsSetting;
use Illuminate\Support\Facades\Crypt;

class SmsSettingService
{
    public function get($key, $default = null)
    {
        try {
            $setting = SmsSetting::where('key', $key)->first();
        } catch (\Throwable $e) {
            return $default;
        }
        if (! $setting || $setting->value === null || $setting->value === '') {
            return $default;
        }
        $value = $setting->value;
        if ($setting->is_secret) {
            try {
                $value = Crypt::decryptString($value);
            } catch (\Throwable $e) {
                return $default;
            }
        }
        return $this->cast($value, $setting->type);
    }

    public function has($key)
    {
        try {
            return SmsSetting::where('key', $key)->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function set($key, $value, $type = 'string', $isSecret = false, $group = 'general')
    {
        if ($isSecret && $value !== null && $value !== '') {
            $value = Crypt::encryptString((string) $value);
        }
        return SmsSetting::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'type' => $type, 'is_secret' => $isSecret, 'group' => $group]
        );
    }

    public function updateMany(array $items)
    {
        foreach ($items as $key => $meta) {
            $this->set($key, $meta['value'] ?? null, $meta['type'] ?? 'string', $meta['is_secret'] ?? false, $meta['group'] ?? 'general');
        }
    }

    public function allForGroup($group)
    {
        try {
            return SmsSetting::where('group', $group)->orderBy('key')->get();
        } catch (\Throwable $e) {
            return collect();
        }
    }

    private function cast($value, $type)
    {
        switch ($type) {
            case 'bool':
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'int':
            case 'integer':
                return (int) $value;
            case 'float':
            case 'decimal':
                return (float) $value;
            case 'json':
                $decoded = json_decode($value, true);
                return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
            default:
                return $value;
        }
    }
}
