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
    public function canonicalPhone(string $phone): string
    {
        $digits = $this->digits($phone);

        if ($digits === '') {
            return '';
        }

        if (str_starts_with($digits, '521') && strlen($digits) === 13) {
            return '52'.substr($digits, 3);
        }

        if (strlen($digits) === 10) {
            return '52'.$digits;
        }

        return $digits;
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
        return preg_replace('/\D+/', '', $value) ?? '';
    }
}
