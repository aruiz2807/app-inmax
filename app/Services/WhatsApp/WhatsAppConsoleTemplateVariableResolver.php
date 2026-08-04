<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsAppConversation;
use App\Models\WhatsAppConsoleTemplate;
use Illuminate\Validation\ValidationException;

class WhatsAppConsoleTemplateVariableResolver
{
    /**
     * Available system values operators can bind when configuring console templates.
     *
     * @return array<string, string>
     */
    public function systemOptions(): array
    {
        return [
            'contact_name' => 'Nombre del contacto WhatsApp',
            'contact_phone' => 'Telefono del contacto WhatsApp',
            'whatsapp_id' => 'WhatsApp ID',
            'user_name' => 'Nombre del usuario vinculado',
            'user_phone' => 'Telefono del usuario vinculado',
            'user_email' => 'Correo del usuario vinculado',
            'policy_number' => 'Numero de membresia',
            'policy_type' => 'Tipo de membresia',
            'policy_status' => 'Estatus de membresia',
            'policy_start_date' => 'Fecha de inicio de membresia',
            'plan_name' => 'Nombre del plan',
            'company_name' => 'Empresa vinculada',
        ];
    }

    /**
     * @param  array<int, string>  $customValues
     * @return array<int, string>
     */
    public function resolveBody(
        WhatsAppConsoleTemplate $template,
        WhatsAppConversation $conversation,
        array $customValues
    ): array {
        return $this->resolve($template->body_variables ?? [], $conversation, $customValues, 'templateBodyValues');
    }

    /**
     * @param  array<int, string>  $customValues
     * @return array<int, string>
     */
    public function resolveButton(
        WhatsAppConsoleTemplate $template,
        WhatsAppConversation $conversation,
        array $customValues
    ): array {
        return $this->resolve($template->button_variables ?? [], $conversation, $customValues, 'templateButtonValues');
    }

    /**
     * @param  array<int, array<string, mixed>>  $variables
     * @param  array<int, string>  $customValues
     * @return array<int, string>
     */
    private function resolve(array $variables, WhatsAppConversation $conversation, array $customValues, string $errorPrefix): array
    {
        $resolved = [];
        $errors = [];

        foreach (array_values($variables) as $index => $variable) {
            $sourceType = (string) ($variable['source_type'] ?? 'custom');
            $required = (bool) ($variable['required'] ?? true);
            $label = (string) ($variable['label'] ?? 'Variable ' . ($index + 1));

            if ($sourceType === 'system') {
                $value = trim($this->systemValue((string) ($variable['system_key'] ?? ''), $conversation));
            } else {
                $value = trim((string) ($customValues[$index] ?? ''));
            }

            if ($required && $value === '') {
                $errors[$errorPrefix . '.' . $index] = 'Completa ' . $label . '.';
            }

            $resolved[] = $value;
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $resolved;
    }

    private function systemValue(string $key, WhatsAppConversation $conversation): string
    {
        $contact = $conversation->contact;
        $user = $contact?->user;
        $policy = $user?->policy;

        return (string) match ($key) {
            'contact_name' => $user?->name ?? $contact?->name,
            'contact_phone' => $contact?->phone ?? $contact?->normalized_phone,
            'whatsapp_id' => $contact?->wa_id,
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
