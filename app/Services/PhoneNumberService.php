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

    public function matches(?string $first, ?string $second): bool
    {
        $a = $this->normalize($first);
        $b = $this->normalize($second);

        return $a !== null && $b !== null && $a === $b;
    }
}
