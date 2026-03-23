<?php

namespace App\Services\Appointments;

use App\Models\Appointment;
use App\Models\WhatsAppSetting;
use App\Services\WhatsApp\WhatsAppCloudApiService;
use App\Services\WhatsApp\WhatsAppDestinationResolver;
use Illuminate\Support\Facades\Log;

class AppointmentRequestNotificationService
{
    public function __construct(
        private readonly WhatsAppCloudApiService $whatsAppService,
        private readonly WhatsAppDestinationResolver $destinationResolver
    ) {}

    /**
     * Notify the assigned doctor/provider about a newly created appointment request.
     *
     * @return array{attempted: bool, ok: bool, reason?: string, status?: int, to?: string, tried_to?: array<int, string>}
     */
    public function send(Appointment $appointment): array
    {
        $appointment->loadMissing('user', 'doctor.user');

        $setting = WhatsAppSetting::query()->first();

        if (! $setting || ! filled($setting->access_token) || ! filled($setting->phone_number_id)) {
            return [
                'attempted' => false,
                'ok' => false,
                'reason' => 'missing_api_credentials',
            ];
        }

        if (! filled($setting->appointment_request_template_name)) {
            return [
                'attempted' => false,
                'ok' => false,
                'reason' => 'missing_template_name',
            ];
        }

        $doctorUser = $appointment->doctor?->user;

        if (! filled($doctorUser?->phone)) {
            return [
                'attempted' => false,
                'ok' => false,
                'reason' => 'invalid_phone',
            ];
        }

        $destinations = $this->destinationResolver->resolve(
            phone: (string) $doctorUser->phone,
            countryCode: (string) ($doctorUser->phone_country_code ?? '52')
        );

        if (empty($destinations)) {
            return [
                'attempted' => false,
                'ok' => false,
                'reason' => 'invalid_phone',
            ];
        }

        $languageCode = $setting->default_language ?: 'es_MX';
        $parameters = [
            (string) $appointment->user?->name,
            $appointment->date->format('d/m/Y'),
            $appointment->time->format('h:i A'),
        ];

        $lastResponse = null;

        foreach ($destinations as $destination) {
            $lastResponse = $this->whatsAppService->sendTemplateMessage(
                setting: $setting,
                to: $destination,
                templateName: $setting->appointment_request_template_name,
                languageCode: $languageCode,
                parameters: $parameters,
                buttonUrlParameters: [],
            );

            if ($lastResponse['ok']) {
                Log::info('WHATSAPP_APPOINTMENT_REQUEST', [
                    'appointment_id' => $appointment->id,
                    'doctor_id' => $appointment->doctor_id,
                    'user_id' => $appointment->user_id,
                    'to' => $destination,
                    'status' => $lastResponse['status'],
                    'template' => $setting->appointment_request_template_name,
                ]);

                return [
                    'attempted' => true,
                    'ok' => true,
                    'status' => $lastResponse['status'],
                    'to' => $destination,
                ];
            }
        }

        Log::info('WHATSAPP_APPOINTMENT_REQUEST', [
            'appointment_id' => $appointment->id,
            'doctor_id' => $appointment->doctor_id,
            'user_id' => $appointment->user_id,
            'status' => $lastResponse['status'] ?? null,
            'template' => $setting->appointment_request_template_name,
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
