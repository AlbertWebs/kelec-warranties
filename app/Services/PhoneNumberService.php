<?php

namespace App\Services;

class PhoneNumberService
{
    public function normalize(?string $number): ?string
    {
        if ($number === null || trim($number) === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $number) ?? '';

        if (str_starts_with($digits, '254') && strlen($digits) === 12) {
            return $digits;
        }

        if (str_starts_with($digits, '0') && strlen($digits) === 10) {
            return '254'.substr($digits, 1);
        }

        if ((str_starts_with($digits, '7') || str_starts_with($digits, '1')) && strlen($digits) === 9) {
            return '254'.$digits;
        }

        return $digits;
    }

    public function isValidKenyanMobile(?string $number): bool
    {
        $normalized = $this->normalize($number);

        return $normalized !== null
            && strlen($normalized) === 12
            && str_starts_with($normalized, '254');
    }

    public function matches(?string $first, ?string $second): bool
    {
        $a = $this->normalize($first);
        $b = $this->normalize($second);

        return $a !== null && $b !== null && $a === $b;
    }

    public function formatDisplay(?string $number): string
    {
        $normalized = $this->normalize($number);
        if ($normalized === null) {
            return trim((string) $number);
        }

        if (str_starts_with($normalized, '254') && strlen($normalized) === 12) {
            $local = '0'.substr($normalized, 3);

            return substr($local, 0, 4).' '.substr($local, 4, 3).' '.substr($local, 7);
        }

        return $normalized;
    }

    public function toTelHref(?string $number): ?string
    {
        $normalized = $this->normalize($number);
        if ($normalized === null || $normalized === '') {
            return null;
        }

        return 'tel:+'.$normalized;
    }
}
