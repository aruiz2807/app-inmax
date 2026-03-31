<?php

namespace Tests\Feature;

use App\Livewire\Appointments\AppointmentFormPage;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Office;
use App\Models\Plan;
use App\Models\Policy;
use App\Models\Service;
use App\Models\Specialty;
use App\Models\User;
use App\Models\WhatsAppSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class AppointmentRequestWhatsAppTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_appointment_sends_request_template_to_doctor(): void
    {
        $scheduler = User::factory()->create([
            'profile' => 'Admin',
            'pin' => '1234',
            'pin_set_at' => now(),
        ]);

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

        $plan = Plan::query()->create([
            'name' => 'Plan Activo',
            'price' => 999.00,
            'type' => 'Individual',
            'status' => 'Active',
        ]);

        Policy::query()->create([
            'user_id' => $member->id,
            'plan_id' => $plan->id,
            'number' => 'POL-2001',
            'type' => 'Individual',
            'status' => 'Active',
        ]);

        $service = Service::query()->create([
            'name' => 'Consulta General',
            'type' => 'Event',
        ]);

        $specialty = Specialty::query()->create([
            'name' => 'Medicina General',
            'service_id' => $service->id,
        ]);

        $specialty->services()->attach($service->id);

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

        WhatsAppSetting::query()->create([
            'api_version' => 'v22.0',
            'phone_number_id' => '113206948334320',
            'access_token' => 'meta_test_token_12345',
            'activation_template_name' => 'pin_activation_template',
            'pin_reset_template_name' => 'pin_reset_template',
            'preregistration_template_name' => 'policy_preregistration_template',
            'appointment_request_template_name' => 'appointment_request_template',
            'appointment_request_body_parameters' => ['member_name', 'appointment_date', 'appointment_time', 'doctor_name'],
            'appointment_request_button_parameters' => [],
            'appointment_completed_template_name' => 'appointment_completed_template',
            'default_language' => 'es_MX',
        ]);

        Http::fake([
            'https://graph.facebook.com/*/messages' => Http::response([
                'messages' => [['id' => 'wamid.APPOINTMENT1']],
            ], 200),
        ]);

        $this->actingAs($scheduler);

        Livewire::test(AppointmentFormPage::class, ['appointmentId' => null])
            ->set('selectedUser', (string) $member->id)
            ->set('selectedDoctor', (string) $doctor->id)
            ->set('selectedDate', '2026-03-25')
            ->set('selectedTime', '11:00')
            ->set('selectedServices', [(string) $service->id])
            ->call('schedule')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('appointments', 1);

        $appointment = Appointment::query()->firstOrFail();

        $this->assertSame($member->id, $appointment->user_id);
        $this->assertSame($doctor->id, $appointment->doctor_id);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/v22.0/113206948334320/messages')
                && $request['to'] === '5213311112233'
                && $request['template']['name'] === 'appointment_request_template'
                && $request['template']['language']['code'] === 'es_MX'
                && count($request['template']['components'] ?? []) === 1
                && ($request['template']['components'][0]['type'] ?? null) === 'body'
                && ($request['template']['components'][0]['parameters'][0]['text'] ?? null) === 'Juan Perez'
                && ($request['template']['components'][0]['parameters'][1]['text'] ?? null) === '25/03/2026'
                && ($request['template']['components'][0]['parameters'][2]['text'] ?? null) === '11:00 AM'
                && ($request['template']['components'][0]['parameters'][3]['text'] ?? null) === 'Dra. Rivera';
        });
    }

    public function test_updating_appointment_does_not_send_request_notification_again(): void
    {
        $scheduler = User::factory()->create([
            'profile' => 'Admin',
            'pin' => '1234',
            'pin_set_at' => now(),
        ]);

        $member = User::factory()->create([
            'profile' => 'User',
        ]);

        $doctorUser = User::factory()->create([
            'profile' => 'Doctor',
            'phone' => '3311113344',
        ]);

        $plan = Plan::query()->create([
            'name' => 'Plan Activo',
            'price' => 999.00,
            'type' => 'Individual',
            'status' => 'Active',
        ]);

        Policy::query()->create([
            'user_id' => $member->id,
            'plan_id' => $plan->id,
            'number' => 'POL-2002',
            'type' => 'Individual',
            'status' => 'Active',
        ]);

        $service = Service::query()->create([
            'name' => 'Consulta Cardiologia',
            'type' => 'Event',
        ]);

        $specialty = Specialty::query()->create([
            'name' => 'Cardiologia',
            'service_id' => $service->id,
        ]);

        $doctor = Doctor::query()->create([
            'user_id' => $doctorUser->id,
            'specialty_id' => $specialty->id,
            'type' => 'Doctor',
            'license' => 'XYZ987',
            'university' => 'UNAM',
            'address' => 'Av. Reforma 100',
            'discount' => 0,
            'commission' => 0,
            'status' => 'Active',
        ]);

        $office = Office::query()->create([
            'name' => 'Consultorio Centro',
            'address' => 'Av. Reforma 100',
            'maps_url' => 'https://maps.example.com/consultorio-centro',
        ]);

        $doctor->offices()->attach($office->id);

        $appointment = Appointment::query()->create([
            'user_id' => $member->id,
            'doctor_id' => $doctor->id,
            'office_id' => $office->id,
            'requested_by_user_id' => $scheduler->id,
            'date' => '2026-03-25',
            'time' => '10:00',
            'status' => 'Booked',
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
            'default_language' => 'es_MX',
        ]);

        Http::fake();

        $this->actingAs($scheduler);

        Livewire::test(AppointmentFormPage::class, ['appointmentId' => $appointment->id])
            ->set('selectedDate', '2026-03-26')
            ->set('selectedTime', '12:00')
            ->call('schedule')
            ->assertHasNoErrors();

        Http::assertNothingSent();
    }
}
