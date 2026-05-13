<?php

namespace App\Livewire\Receptionist;

use App\Models\Appointment;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

class AppointmentsPage extends Component
{
    #[Url(as: 'tab')]
    public string $tab = 'all';
    public ?Appointment $selectedAppointment = null;

    public function setTab(string $tab): void
    {
        if (! in_array($tab, ['all', 'pending', 'paid'], true)) {
            return;
        }

        $this->tab = $tab;
    }

    #[On('showReceptionistAppointmentDetail')]
    public function openDetails(int $appointmentId): void
    {
        $doctorIds = Auth::user()->staffDoctors()->pluck('doctors.id');

        $appointment = Appointment::query()
            ->with([
                'user.policy',
                'doctor.user',
                'doctor.specialty',
                'services.service',
            ])
            ->whereIn('doctor_id', $doctorIds)
            ->whereKey($appointmentId)
            ->first();

        if (! $appointment) {
            return;
        }

        $this->selectedAppointment = $appointment;
        $this->dispatch('open-receptionist-appointment-detail-modal');
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.receptionist.appointments-page');
    }
}
