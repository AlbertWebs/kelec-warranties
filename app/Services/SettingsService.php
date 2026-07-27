<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    public function get(string $key, mixed $default = null): mixed
    {
        $settings = $this->all();

        return $settings[$key] ?? $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return Cache::remember('system_settings', 300, function () {
            return SystemSetting::query()
                ->get()
                ->mapWithKeys(fn (SystemSetting $setting) => [$setting->key => $setting->decoded_value])
                ->all();
        });
    }

    public function set(string $key, mixed $value, string $group = 'general', string $type = 'string', bool $encrypt = false): void
    {
        SystemSetting::setValue($key, $value, $group, $type, $encrypt);
        Cache::forget('system_settings');
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function setMany(array $values, string $group = 'general'): void
    {
        foreach ($values as $key => $payload) {
            if (is_array($payload) && array_key_exists('value', $payload)) {
                $this->set(
                    $key,
                    $payload['value'],
                    $payload['group'] ?? $group,
                    $payload['type'] ?? 'string',
                    $payload['encrypt'] ?? false
                );
            } else {
                $this->set($key, $payload, $group);
            }
        }
    }
}
