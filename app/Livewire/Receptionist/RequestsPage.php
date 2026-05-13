<?php

namespace App\Livewire\Receptionist;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

class RequestsPage extends Component
{
    #[Url(as: 'tab')]
    public string $tab = 'all';

    public function setTab(string $tab): void
    {
        if (! in_array($tab, ['pending', 'booked', 'rejected', 'all'], true)) {
            return;
        }

        $this->tab = $tab;
    }

    #[On('acceptReceptionistRequest')]
    public function acceptRequest(int $appointmentId): void
    {
        $appointment = $this->getBaseQuery()
            ->whereKey($appointmentId)
            ->first();

        if (! $appointment || $appointment->status !== AppointmentStatus::REQUESTED) {
            return;
        }

        $appointment->update([
            'status' => AppointmentStatus::BOOKED,
        ]);

        $this->dispatch(
            'notify',
            type: 'success',
            content: 'Solicitud aceptada correctamente.',
            duration: 3500
        );

        $this->dispatch('pg:eventRefresh-receptionistRequestsTable');
    }

    #[On('rejectReceptionistRequest')]
    public function rejectRequest(int $appointmentId): void
    {
        $appointment = $this->getBaseQuery()
            ->whereKey($appointmentId)
            ->first();

        if (! $appointment || $appointment->status !== AppointmentStatus::REQUESTED) {
            return;
        }

        $appointment->update([
            'status' => AppointmentStatus::REJECTED,
        ]);

        $this->dispatch(
            'notify',
            type: 'success',
            content: 'Solicitud rechazada correctamente.',
            duration: 3500
        );

        $this->dispatch('pg:eventRefresh-receptionistRequestsTable');
    }

    public function getPendingCountProperty(): int
    {
        return (clone $this->getBaseQuery())
            ->where('status', AppointmentStatus::REQUESTED)
            ->count();
    }

    public function getBookedCountProperty(): int
    {
        return (clone $this->getBaseQuery())
            ->where('status', AppointmentStatus::BOOKED)
            ->count();
    }

    public function getRejectedCountProperty(): int
    {
        return (clone $this->getBaseQuery())
            ->where('status', AppointmentStatus::REJECTED)
            ->count();
    }

    private function getBaseQuery(): Builder
    {
        $doctorIds = Auth::user()->staffDoctors()->pluck('doctors.id');

        return Appointment::query()->whereIn('doctor_id', $doctorIds);
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.receptionist.requests-page');
    }
}
