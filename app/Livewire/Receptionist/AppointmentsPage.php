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

class AppointmentsPage extends Component
{
    #[Url(as: 'tab')]
    public string $tab = 'all';
    public ?string $dateFrom = null;
    public ?string $dateTo = null;
    public ?Appointment $selectedAppointment = null;

    public function mount(): void
    {
        $this->dateFrom = Carbon::now()->startOfMonth()->toDateString();
        $this->dateTo = Carbon::now()->endOfMonth()->toDateString();
    }

    public function setTab(string $tab): void
    {
        if (! in_array($tab, ['all', 'pending', 'cancelled', 'paid'], true)) {
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
                'office',
                'services.service',
            ])
            ->where(function (Builder $query) use ($doctorIds) {
                $query
                    ->whereIn('appointments.doctor_id', $doctorIds)
                    ->orWhere(function (Builder $officeQuery) use ($doctorIds) {
                        $officeQuery
                            ->whereNull('appointments.doctor_id')
                            ->whereExists(function ($existsQuery) use ($doctorIds) {
                                $existsQuery
                                    ->selectRaw('1')
                                    ->from('office_doctors')
                                    ->whereColumn('office_doctors.office_id', 'appointments.office_id')
                                    ->whereIn('office_doctors.doctor_id', $doctorIds);
                            });
                    });
            })
            ->whereKey($appointmentId)
            ->first();

        if (! $appointment) {
            return;
        }

        $this->selectedAppointment = $appointment;
        $this->dispatch('open-receptionist-appointment-detail-modal');
    }

    #[On('receptionistAppointmentsDateRangeChanged')]
    public function syncDateRange(?string $dateFrom = null, ?string $dateTo = null): void
    {
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
    }

    public function getPendingCountProperty(): int
    {
        return (clone $this->getBaseQuery())
            ->whereNull('user_payment')
            ->whereNotIn('status', [
                AppointmentStatus::REJECTED->value,
                AppointmentStatus::CANCELLED->value,
                AppointmentStatus::NO_SHOW->value,
            ])
            ->count();
    }

    public function getPaidCountProperty(): int
    {
        return (clone $this->getBaseQuery())
            ->whereNotNull('user_payment')
            ->count();
    }

    public function getCancelledCountProperty(): int
    {
        return (clone $this->getBaseQuery())
            ->whereIn('status', [
                AppointmentStatus::CANCELLED->value,
                AppointmentStatus::NO_SHOW->value,
            ])
            ->count();
    }

    private function getBaseQuery(): Builder
    {
        $doctorIds = Auth::user()->staffDoctors()->pluck('doctors.id');

        return Appointment::query()
            ->where(function (Builder $query) use ($doctorIds) {
                $query
                    ->whereIn('appointments.doctor_id', $doctorIds)
                    ->orWhere(function (Builder $officeQuery) use ($doctorIds) {
                        $officeQuery
                            ->whereNull('appointments.doctor_id')
                            ->whereExists(function ($existsQuery) use ($doctorIds) {
                                $existsQuery
                                    ->selectRaw('1')
                                    ->from('office_doctors')
                                    ->whereColumn('office_doctors.office_id', 'appointments.office_id')
                                    ->whereIn('office_doctors.doctor_id', $doctorIds);
                            });
                    });
                            })
                            ->whereNotIn('appointments.status', [
                            AppointmentStatus::REQUESTED->value,
                            AppointmentStatus::REJECTED->value,
                            ])
                            ->when($this->dateFrom, fn (Builder $query) => $query->whereDate('appointments.date', '>=', $this->dateFrom))
                            ->when($this->dateTo, fn (Builder $query) => $query->whereDate('appointments.date', '<=', $this->dateTo));
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.receptionist.appointments-page');
    }
}
