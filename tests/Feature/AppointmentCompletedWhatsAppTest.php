<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppointmentNote;
use App\Models\Doctor;
use App\Models\Service;
use App\Models\Specialty;
use App\Models\User;
use App\Models\WhatsAppSetting;
use App\Services\Appointments\AppointmentCompletedNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AppointmentCompletedWhatsAppTest extends TestCase
{
    use RefreshDatabase;

    public function test_completed_appointment_uses_configured_template_parameters(): void
    {
        $member = User::factory()->create([
            'profile' => 'User',
            'name' => 'Juan Perez',
            'phone' => '3312345678',
        ]);

        $doctorUser = User::factory()->create([
            'profile' => 'Doctor',
            'name' => 'Dra. Rivera',
            'phone' => '3311112233',
        ]);

        $service = Service::query()->create([
            'name' => 'Consulta General',
            'type' => 'Event',
        ]);

        $specialty = Specialty::query()->create([
            'name' => 'Medicina General',
            'service_id' => $service->id,
        ]);

        $doctor = Doctor::query()->create([
            'user_id' => $doctorUser->id,
            'specialty_id' => $specialty->id,
            'type' => 'Doctor',
            'license' => 'ABC123',
            'university' => 'UdeG',
            'address' => 'Av. Siempre Viva 123',
            'discount' => 0,
            'commission' => 0,
            'status' => 'Active',
        ]);

        $appointment = Appointment::query()->create([
            'user_id' => $member->id,
            'doctor_id' => $doctor->id,
            'date' => '2026-03-30',
            'time' => '11:00',
            'status' => 'Completed',
        ]);

        AppointmentNote::query()->create([
            'appointment_id' => $appointment->id,
            'notes' => 'Completada',
            'created_at' => now()->setDate(2026, 3, 30)->setTime(14, 0),
            'updated_at' => now()->setDate(2026, 3, 30)->setTime(14, 0),
        ]);

        WhatsAppSetting::query()->create([
            'api_version' => 'v22.0',
            'phone_number_id' => '113206948334320',
            'access_token' => 'meta_test_token_12345',
            'activation_template_name' => 'pin_activation_template',
            'pin_reset_template_name' => 'pin_reset_template',
            'preregistration_template_name' => 'policy_preregistration_template',
            'appointment_request_template_name' => 'appointment_request_template',
            'appointment_completed_template_name' => 'appointment_completed_template',
            'appointment_completed_language_code' => 'es',
            'appointment_completed_body_parameters' => ['member_name', 'completed_date', 'doctor_name'],
            'appointment_completed_button_parameters' => [],
            'default_language' => 'en_US',
        ]);

        Http::fake([
            'https://graph.facebook.com/*/messages' => Http::response([
                'messages' => [['id' => 'wamid.APPOINTMENT_COMPLETED']],
            ], 200),
        ]);

        $result = app(AppointmentCompletedNotificationService::class)->send($appointment->fresh());

        $this->assertTrue($result['attempted']);
        $this->assertTrue($result['ok']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/v22.0/113206948334320/messages')
                && $request['to'] === '5213312345678'
                && $request['template']['name'] === 'appointment_completed_template'
                && $request['template']['language']['code'] === 'es'
                && ($request['template']['components'][0]['parameters'][0]['text'] ?? null) === 'Juan Perez'
                && ($request['template']['components'][0]['parameters'][1]['text'] ?? null) === '30/03/2026'
                && ($request['template']['components'][0]['parameters'][2]['text'] ?? null) === 'Dra. Rivera';
        });
    }
}
