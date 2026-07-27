<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class SystemSetting extends Model
{
    protected $fillable = [
        'group',
        'key',
        'value',
        'is_encrypted',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'is_encrypted' => 'boolean',
        ];
    }

    public function getDecodedValueAttribute(): mixed
    {
        if ($this->value === null) {
            return null;
        }

        $value = $this->is_encrypted ? Crypt::decryptString($this->value) : $this->value;

        return match ($this->type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'json' => json_decode($value, true),
            default => $value,
        };
    }

    public static function setValue(string $key, mixed $value, string $group = 'general', string $type = 'string', bool $encrypt = false): self
    {
        $stored = match ($type) {
            'boolean' => $value ? '1' : '0',
            'json' => is_string($value) ? $value : json_encode($value),
            default => (string) $value,
        };

        if ($encrypt && $stored !== '') {
            $stored = Crypt::encryptString($stored);
        }

        return static::updateOrCreate(
            ['key' => $key],
            [
                'group' => $group,
                'value' => $stored,
                'is_encrypted' => $encrypt,
                'type' => $type,
            ]
        );
    }
}
