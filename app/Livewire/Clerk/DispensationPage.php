<?php

namespace App\Livewire\Clerk;

use App\Models\Appointment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

class DispensationPage extends Component
{
    #[Url(as: 'tab')]
    public string $tab = 'pending';

    public ?array $selectedAppointment = null;

    public function setTab(string $tab): void
    {
        if (! in_array($tab, ['all', 'pending', 'partial', 'filled', 'cancelled'], true)) {
            return;
        }

        $this->tab = $tab;
    }

    public function getPendingCountProperty(): int
    {
        return (clone $this->getBaseQuery())
            ->where('status_prescription', 'Pending')
            ->count();
    }

    public function getPartialCountProperty(): int
    {
        return (clone $this->getBaseQuery())
            ->where('status_prescription', 'Partial')
            ->count();
    }

    public function getFilledCountProperty(): int
    {
        return (clone $this->getBaseQuery())
            ->where('status_prescription', 'Filled')
            ->count();
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.clerk.dispensation-page');
    }

    private function getBaseQuery(): Builder
    {
        return Appointment::query()
            ->whereNotNull('status_prescription')
            ->whereHas('doctor', fn (Builder $query) => $query->where('type', 'Doctor'));
    }

    #[On('showDispensationDetails')]
    public function openDetails(int $appointmentId): void
    {
        $appointment = Appointment::query()
            ->with([
                'user.policy',
                'doctor.user',
                'prescriptions.medication',
            ])
            ->whereNotNull('status_prescription')
            ->whereKey($appointmentId)
            ->first();

        if (! $appointment) {
            return;
        }

        $appointmentDateTime = Carbon::parse(
            $appointment->date->format('Y-m-d').' '.Carbon::parse($appointment->time)->format('H:i:s')
        );

        $isDispensed = in_array((string) $appointment->status_prescription, ['Filled', 'Partial'], true);

        $this->selectedAppointment = [
            'patient_name' => $appointment->user?->name ?? 'Sin paciente',
            'membership_number' => $appointment->user?->policy?->number ?? '-',
            'prescriber_doctor' => $appointment->doctor?->user?->name ?? 'Sin médico',
            'appointment_date_label' => $appointmentDateTime->format('d/m/Y H:i'),
            'is_dispensed' => $isDispensed,
            'dispensed_at_label' => $isDispensed ? 'Surtida' : 'Pendiente',
            'prescribed_medications' => $appointment->prescriptions
                ->map(fn ($prescription) => [
                    'name' => $prescription->medication?->name ?? 'Medicamento sin nombre',
                    'presentation' => $prescription->medication?->packaging ?? '-',
                    'dose' => trim(($prescription->dose ?? '').' / '.($prescription->frequency ?? '')),
                    'quantity' => (int) $prescription->quantity,
                    'notes' => $prescription->duration ?? '-',
                    'status' => $prescription->status ?? 'Prescribed',
                ])
                ->values()
                ->toArray(),
        ];

        $this->dispatch('open-dispensation-details-modal');
    }
}
