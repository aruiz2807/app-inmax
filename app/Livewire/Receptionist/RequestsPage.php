<?php

namespace App\Livewire\Receptionist;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

class RequestsPage extends Component
{
    #[Url(as: 'tab')]
    public string $tab = 'pending';
    public ?string $dateFrom = null;
    public ?string $dateTo = null;
    public ?Appointment $selectedRequest = null;

    public function mount(): void
    {
        $this->dateFrom = Carbon::now()->startOfMonth()->toDateString();
        $this->dateTo = Carbon::now()->endOfMonth()->toDateString();
    }

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

    #[On('showReceptionistRequestDetail')]
    public function openDetails(int $appointmentId): void
    {
        $request = $this->getBaseQuery()
            ->with([
                'user.policy',
                'doctor.user',
                'doctor.specialty',
                'services.service',
            ])
            ->whereKey($appointmentId)
            ->first();

        if (! $request) {
            return;
        }

        $this->selectedRequest = $request;
        $this->dispatch('open-receptionist-request-detail-modal');
    }

    #[On('receptionistRequestsDateRangeChanged')]
    public function syncDateRange(?string $dateFrom = null, ?string $dateTo = null): void
    {
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
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
            ->whereNotIn('status', [
                AppointmentStatus::REQUESTED,
                AppointmentStatus::REJECTED,
            ])
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

        return Appointment::query()
            ->whereIn('doctor_id', $doctorIds)
            ->when($this->dateFrom, fn (Builder $query) => $query->whereDate('appointments.date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn (Builder $query) => $query->whereDate('appointments.date', '<=', $this->dateTo));
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.receptionist.requests-page');
    }
}
