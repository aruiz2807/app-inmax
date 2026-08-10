<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Livewire\Dispatcher\TransportsPage;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TransportsPageCancelTest extends TestCase
{
    use RefreshDatabase;

    public function test_cancel_transport_appends_reason_to_existing_comments_and_marks_appointment_as_cancelled(): void
    {
        $dispatcher = User::factory()->create([
            'profile' => 'Admin',
            'pin' => '1234',
            'pin_set_at' => now(),
        ]);

        $patient = User::factory()->create([
            'profile' => 'User',
            'name' => 'Paciente Test',
        ]);

        $doctorUser = User::factory()->create([
            'profile' => 'Doctor',
            'name' => 'Dr. Test',
        ]);

        $doctor = Doctor::query()->create([
            'user_id' => $doctorUser->id,
            'specialty_id' => 1,
            'type' => 'Doctor',
            'license' => 'ABC123',
            'university' => 'UdeG',
            'address' => 'Av. Siempre Viva 123',
            'discount' => 0,
            'commission' => 0,
            'status' => 'Active',
        ]);

        $dispatcher->staffDoctors()->attach($doctor->id);

        $appointment = Appointment::query()->create([
            'user_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'requested_by_user_id' => $dispatcher->id,
            'date' => now()->toDateString(),
            'time' => now()->toTimeString(),
            'origin_address' => 'Origen de prueba',
            'destination_address' => 'Destino de prueba',
            'status' => AppointmentStatus::BOOKED,
            'comments' => 'Comentario anterior',
        ]);

        $this->actingAs($dispatcher);

        Livewire::test(TransportsPage::class)
            ->call('openCancelTransport', $appointment->id)
            ->set('cancelReason', 'Paciente no pudo asistir')
            ->call('confirmCancelTransport')
            ->assertHasNoErrors();

        $appointment->refresh();

        $this->assertSame(AppointmentStatus::CANCELLED, $appointment->status);
        $this->assertStringContainsString('Comentario anterior', $appointment->comments);
        $this->assertStringContainsString('Cancelado por: Paciente no pudo asistir', $appointment->comments);
    }
}
