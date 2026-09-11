<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsAppMarketingCampaignRecipient;

class WhatsAppMarketingVariableResolver
{
    /**
     * @param  array<int, array<string, mixed>>  $mappings
     * @return array<int, string>
     */
    public function resolve(WhatsAppMarketingCampaignRecipient $recipient, array $mappings): array
    {
        return collect($mappings)
            ->map(fn (array $mapping): string => trim($this->value($recipient, $mapping)))
            ->values()
            ->all();
    }

    public function value(WhatsAppMarketingCampaignRecipient $recipient, array $mapping): string
    {
        $sourceType = (string) ($mapping['source_type'] ?? 'column');

        return match ($sourceType) {
            'system' => $this->systemValue($recipient, (string) ($mapping['system_key'] ?? '')),
            'static' => (string) ($mapping['static_value'] ?? ''),
            default => (string) data_get($recipient->row_data ?? [], (string) ($mapping['column_key'] ?? '')),
        };
    }

    private function systemValue(WhatsAppMarketingCampaignRecipient $recipient, string $key): string
    {
        $contact = $recipient->phone_normalized
            ? \App\Models\WhatsAppContact::query()
                ->with('user.policy.plan', 'user.company')
                ->where('normalized_phone', $recipient->phone_normalized)
                ->first()
            : null;
        $user = $contact?->user;
        $policy = $user?->policy;

        return (string) match ($key) {
            'contact_name' => $recipient->customer_name ?: $contact?->name ?: $user?->name,
            'contact_phone' => $recipient->phone_normalized ?: $recipient->phone_raw,
            'user_name' => $user?->name,
            'user_phone' => $user?->phone,
            'user_email' => $user?->contact_email ?? $user?->email,
            'policy_number' => $policy?->number,
            'policy_type' => $policy?->type,
            'policy_status' => $policy?->status,
            'policy_start_date' => $policy?->start_date?->format('d/m/Y'),
            'plan_name' => $policy?->plan?->name,
            'company_name' => $user?->company?->name,
            default => '',
        };
    }
}
