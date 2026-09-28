<?php

namespace App\Services\WhatsApp;

use App\Models\User;
use App\Models\WhatsAppContact;

class WhatsAppContactService
{
    /**
     * Resolve or create a WhatsApp contact using a canonical phone value.
     */
    public function findOrCreate(string $phone, ?string $name = null, ?string $waId = null): WhatsAppContact
    {
        $normalizedPhone = $this->canonicalPhone($waId ?: $phone);
        $normalizedWaId = $waId ? $this->digits((string) $waId) : null;

        $contact = WhatsAppContact::query()
            ->where(function ($query) use ($normalizedWaId, $normalizedPhone) {
                if ($normalizedWaId) {
                    $query->where('wa_id', $normalizedWaId);
                }

                $query->orWhere('normalized_phone', $normalizedPhone);
            })
            ->first();

        $userId = $this->resolveUserId($normalizedPhone);

        if (! $contact) {
            return WhatsAppContact::query()->create([
                'user_id' => $userId,
                'name' => $name,
                'phone' => $phone,
                'normalized_phone' => $normalizedPhone,
                'wa_id' => $normalizedWaId,
            ]);
        }

        $contact->forceFill([
            'user_id' => $contact->user_id ?: $userId,
            'name' => $name ?: $contact->name,
            'phone' => $phone ?: $contact->phone,
            'normalized_phone' => $normalizedPhone,
            'wa_id' => $normalizedWaId ?: $contact->wa_id,
        ])->save();

        return $contact->refresh();
    }

    /**
     * Normalize a phone number to a canonical searchable value.
     */
    public function canonicalPhone(string $phone, ?string $countryCode = '52'): string
    {
        $digits = $this->digits($phone);
        $normalizedCountryCode = $this->digits((string) $countryCode) ?: '52';

        if ($digits === '') {
            return '';
        }

        if ($normalizedCountryCode === '52' && str_starts_with($digits, '521') && strlen($digits) === 13) {
            return '52'.substr($digits, 3);
        }

        if (strlen($digits) === 10) {
            return $normalizedCountryCode.$digits;
        }

        if (str_starts_with($digits, $normalizedCountryCode) && strlen($digits) === strlen($normalizedCountryCode) + 10) {
            return $digits;
        }

        return $digits;
    }

    /**
     * Resolve the canonical destination for a console contact.
     */
    public function destinationPhone(WhatsAppContact $contact): string
    {
        if (filled($contact->wa_id)) {
            return $this->digits((string) $contact->wa_id);
        }

        $user = $contact->user;
        $phone = $user?->clean_phone ?: $contact->phone ?: $contact->normalized_phone;

        return $this->canonicalPhone(
            (string) $phone,
            (string) ($user?->phone_country_code ?? '52')
        );
    }

    /**
     * Derive the user's local phone value from a canonical WhatsApp number.
     */
    public function localPhone(string $canonicalPhone): string
    {
        if (str_starts_with($canonicalPhone, '52') && strlen($canonicalPhone) === 12) {
            return substr($canonicalPhone, 2);
        }

        return $canonicalPhone;
    }

    /**
     * Resolve an internal user by the canonical WhatsApp number.
     */
    private function resolveUserId(string $canonicalPhone): ?int
    {
        if ($canonicalPhone === '') {
            return null;
        }

        $localPhone = $this->localPhone($canonicalPhone);

        return User::query()
            ->where('phone', $localPhone)
            ->orWhere('phone', $canonicalPhone)
            ->value('id');
    }

    /**
     * Keep only digits from phone values.
     */
    private function digits(string $value): string
    {
        $basePhone = explode('-', $value)[0];

        return preg_replace('/\D+/', '', $basePhone) ?? '';
    }
}
