<?php

namespace App\Services\WhatsApp;

class WhatsAppDestinationResolver
{
    /**
     * Resolve destination phone(s) for WhatsApp delivery.
     *
     * For Mexico numbers, first try 521XXXXXXXXXX and fallback to 52XXXXXXXXXX.
     *
     * @return array<int, string>
     */
    public function resolve(string $phone, ?string $countryCode = '52'): array
    {
        $normalizedCountryCode = $this->digits((string) $countryCode) ?: '52';
        $normalizedPhone = $this->digits($phone);

        if ($normalizedPhone === '') {
            return [];
        }

        if ($normalizedCountryCode !== '52') {
            return [str_starts_with($normalizedPhone, $normalizedCountryCode)
                ? $normalizedPhone
                : $normalizedCountryCode.$normalizedPhone];
        }

        if (strlen($normalizedPhone) === 10) {
            return [
                '521'.$normalizedPhone,
                '52'.$normalizedPhone,
            ];
        }

        if (str_starts_with($normalizedPhone, '521') && strlen($normalizedPhone) === 13) {
            return [
                $normalizedPhone,
                '52'.substr($normalizedPhone, 3),
            ];
        }

        if (str_starts_with($normalizedPhone, '52') && strlen($normalizedPhone) === 12) {
            return [
                '521'.substr($normalizedPhone, 2),
                $normalizedPhone,
            ];
        }

        return [$normalizedPhone];
    }

    /**
     * Keep only digits from phone/code values.
     */
    private function digits(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? '';
    }
}
