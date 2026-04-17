<?php

namespace App\Services\Appointments;

use App\Models\Appointment;
use App\Models\WhatsAppSetting;
use App\Services\WhatsApp\WhatsAppCloudApiService;
use App\Services\WhatsApp\WhatsAppDestinationResolver;
use App\Services\WhatsApp\WhatsAppTemplateParameterResolver;
use Illuminate\Support\Facades\Log;

class AppointmentCompletedNotificationService
{
    public function __construct(
        private readonly WhatsAppCloudApiService $whatsAppService,
        private readonly WhatsAppDestinationResolver $destinationResolver,
        private readonly WhatsAppTemplateParameterResolver $parameterResolver
    ) {}

    /**
     * Notify the member when the appointment has been completed.
     *
     * @return array{attempted: bool, ok: bool, reason?: string, status?: int, to?: string, tried_to?: array<int, string>}
     */
    public function send(Appointment $appointment): array
    {
        $appointment->loadMissing('user', 'doctor.user', 'note');

        $setting = WhatsAppSetting::query()->first();

        if (! $setting || ! filled($setting->access_token) || ! filled($setting->phone_number_id)) {
            return [
                'attempted' => false,
                'ok' => false,
                'reason' => 'missing_api_credentials',
            ];
        }

        if (! filled($setting->appointment_completed_template_name)) {
            return [
                'attempted' => false,
                'ok' => false,
                'reason' => 'missing_template_name',
            ];
        }

        $member = $appointment->user;

        if (! filled($member?->phone)) {
            return [
                'attempted' => false,
                'ok' => false,
                'reason' => 'invalid_phone',
            ];
        }

        $destinations = $this->destinationResolver->resolve(
            phone: (string) $member->phone,
            countryCode: (string) ($member->phone_country_code ?? '52')
        );

        if ($destinations === []) {
            return [
                'attempted' => false,
                'ok' => false,
                'reason' => 'invalid_phone',
            ];
        }

        $parameterContext = [
            'appointment' => $appointment,
            'member' => $member,
            'doctor_name' => $appointment->doctor?->user?->name,
            'completed_at' => $appointment->note?->created_at ?? now(),
        ];
        $languageCode = $setting->appointment_completed_language_code ?: ($setting->default_language ?: 'es_MX');
        $parameters = $this->parameterResolver->resolve(
            $setting->appointment_completed_body_parameters,
            WhatsAppTemplateParameterResolver::APPOINTMENT_COMPLETED_BODY,
            $parameterContext
        );
        $buttonParameters = $this->parameterResolver->resolve(
            $setting->appointment_completed_button_parameters,
            WhatsAppTemplateParameterResolver::APPOINTMENT_COMPLETED_BUTTON,
            $parameterContext
        );

        $lastResponse = null;

        foreach ($destinations as $destination) {
            $lastResponse = $this->whatsAppService->sendTemplateMessage(
                setting: $setting,
                to: $destination,
                templateName: $setting->appointment_completed_template_name,
                languageCode: $languageCode,
                parameters: $parameters,
                buttonUrlParameters: $buttonParameters,
            );

            if ($lastResponse['ok']) {
                Log::info('WHATSAPP_APPOINTMENT_COMPLETED', [
                    'appointment_id' => $appointment->id,
                    'doctor_id' => $appointment->doctor_id,
                    'user_id' => $appointment->user_id,
                    'to' => $destination,
                    'status' => $lastResponse['status'],
                    'template' => $setting->appointment_completed_template_name,
                ]);

                return [
                    'attempted' => true,
                    'ok' => true,
                    'status' => $lastResponse['status'],
                    'to' => $destination,
                ];
            }
        }

        Log::info('WHATSAPP_APPOINTMENT_COMPLETED', [
            'appointment_id' => $appointment->id,
            'doctor_id' => $appointment->doctor_id,
            'user_id' => $appointment->user_id,
            'status' => $lastResponse['status'] ?? null,
            'template' => $setting->appointment_completed_template_name,
            'tried_to' => $destinations,
            'response' => $lastResponse['data'] ?? [],
        ]);

        return [
            'attempted' => true,
            'ok' => false,
            'status' => $lastResponse['status'] ?? null,
            'reason' => 'all_destinations_failed',
            'tried_to' => $destinations,
        ];
    }
}
