<?php

namespace App\Models\Sms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SmsHomeSlide extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'badge', 'title', 'description', 'image_url', 'card_title', 'card_value',
        'card_description', 'translations', 'is_enabled', 'sort_order',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'translations' => 'array',
    ];

    public function localizedCopy(?string $locale = null): array
    {
        $locale = $locale ?: app()->getLocale();
        $default = config('sms.locale.default', 'zh_CN');
        $translations = is_array($this->translations) ? $this->translations : [];

        $fallback = [
            'badge' => $this->badge,
            'title' => $this->title,
            'description' => $this->description,
            'card_title' => $this->card_title,
            'card_value' => $this->card_value,
            'card_description' => $this->card_description,
        ];

        return $this->mergeCopy($fallback, $translations[$default] ?? [], $translations[$locale] ?? []);
    }

    private function mergeCopy(array ...$copies): array
    {
        $merged = [];
        foreach ($copies as $copy) {
            foreach (['badge', 'title', 'description', 'card_title', 'card_value', 'card_description'] as $field) {
                if (array_key_exists($field, $copy) && $copy[$field] !== null && $copy[$field] !== '') {
                    $merged[$field] = $copy[$field];
                }
            }
        }

        return $merged;
    }
}
