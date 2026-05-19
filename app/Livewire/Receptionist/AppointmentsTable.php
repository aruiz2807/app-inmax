<?php

namespace App\Livewire\Receptionist;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class AppointmentsTable extends PowerGridComponent
{
    public string $tableName = 'receptionistAppointmentsTable';
    public string $tab = 'all';
    public string $sortField = 'date';
    public string $sortDirection = 'desc';
    public ?string $dateFrom = null;
    public ?string $dateTo = null;

    public function mount(): void
    {
        parent::mount();

        $this->dateFrom = Carbon::now()->startOfMonth()->toDateString();
        $this->dateTo = Carbon::now()->toDateString();
    }

    public function setUp(): array
    {
        return [
            PowerGrid::header()
                ->showSearchInput()
                ->showToggleColumns()
                ->includeViewOnTop('livewire.receptionist.appointments-date-presets'),
            PowerGrid::footer()
                ->showPerPage()
                ->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        $doctorIds = Auth::user()->staffDoctors()->pluck('doctors.id');

        return Appointment::query()
            ->select('appointments.*')
            ->leftJoin('users as patients', 'patients.id', '=', 'appointments.user_id')
            ->leftJoin('doctors', 'doctors.id', '=', 'appointments.doctor_id')
            ->leftJoin('users as doctor_users', 'doctor_users.id', '=', 'doctors.user_id')
            ->leftJoin('policies as patient_policies', 'patient_policies.user_id', '=', 'appointments.user_id')
            ->with(['user:id,name', 'user.policy:id,user_id,number', 'doctor:id,user_id', 'doctor.user:id,name', 'office:id,name', 'note:id,appointment_id', 'services:id,appointment_id,covered'])
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
            ->when($this->tab === 'pending', fn (Builder $query) => $query->where(function (Builder $pendingQuery) {
                $pendingQuery
                    ->whereNull('user_payment')
                    ->where('appointments.status', AppointmentStatus::BOOKED->value);
            }))
            ->when($this->tab === 'cancelled', fn (Builder $query) => $query->whereIn('appointments.status', [
                AppointmentStatus::CANCELLED->value,
                AppointmentStatus::NO_SHOW->value,
            ]))
            ->when($this->tab === 'paid', fn (Builder $query) => $query->whereNotNull('user_payment'))
            ->when($this->dateFrom, fn (Builder $query) => $query->whereDate('appointments.date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn (Builder $query) => $query->whereDate('appointments.date', '<=', $this->dateTo));
    }

    public function applyPreset(string $preset): void
    {
        [$start, $end] = match ($preset) {
            'last7' => [Carbon::now()->subDays(6)->startOfDay(), Carbon::now()->endOfDay()],
            'month' => [Carbon::now()->startOfMonth()->startOfDay(), Carbon::now()->endOfDay()],
            default => [null, null],
        };

        if ($start && $end) {
            $this->dateFrom = $start->toDateString();
            $this->dateTo = $end->toDateString();
        }
    }

    public function clearDateRange(): void
    {
        $this->dateFrom = null;
        $this->dateTo = null;
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function beforeSearch(string $field, ?string $search): ?string
    {
        if ($field !== 'status' || blank($search)) {
            return $search;
        }

        $normalized = strtolower(trim((string) $search));
        $normalized = str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $normalized);

        return match (true) {
            str_contains($normalized, 'solicit') => 'Requested',
            str_contains($normalized, 'rechaz') => 'Rejected',
            str_contains($normalized, 'agend') => 'Booked',
            str_contains($normalized, 'cancel') => 'Cancelled',
            str_contains($normalized, 'atendid') => 'Completed',
            str_contains($normalized, 'no se present') || str_contains($normalized, 'no-show') || str_contains($normalized, 'noshow') => 'No-show',
            default => $search,
        };
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('date_formatted', fn (Appointment $appointment) => $appointment->date?->format('d/m/Y'))
            ->add('time_formatted', fn (Appointment $appointment) => $appointment->time?->format('H:i'))
            ->add('patient_name', fn (Appointment $appointment) => e($appointment->user?->name ?? 'N/A'))
            ->add('membership_number', fn (Appointment $appointment) => e($appointment->user?->policy?->number ?? '-'))
            ->add('doctor_name', fn (Appointment $appointment) => e($appointment->doctor?->user?->name ?? $appointment->office?->name ?? 'N/A'))
            ->add('status_badge', fn (Appointment $appointment) => Blade::render('<x-status-badge status="'.$appointment->status?->value.'" />'))
            ->add('payment_status_badge', function (Appointment $appointment): string {
                if (is_null($appointment->user_payment)) {
                    return Blade::render('<x-status-badge status="Pending" />');
                }

                return Blade::render('<x-status-badge status="Paid" />');
            })
            ->add('amount_formatted', function (Appointment $appointment): string {
                $allCovered = $appointment->services->isNotEmpty() && $appointment->services->every(fn ($s) => (bool) $s->covered);

                if ($allCovered && is_null($appointment->user_payment)) {
                    return '$0.00';
                }

                return '$'.number_format((float) $appointment->user_payment, 2);
            })
            ->add('payment_button', function (Appointment $appointment): string {
                $isPaid = !is_null($appointment->user_payment);
                $isCompleted = $appointment->status == \App\Enums\AppointmentStatus::COMPLETED;
                $hasNote = ! is_null($appointment->note?->id);
                $allCovered = $appointment->services->isNotEmpty() && $appointment->services->every(fn ($s) => (bool) $s->covered);

                $detailButton = '<button type="button" onclick="window.dispatchEvent(new CustomEvent(\'open-receptionist-appointment-detail\', { detail: { appointmentId: '.$appointment->id.' } }))" class="bg-teal-600 text-white px-3 py-1 rounded">Detalle</button>';

                if ($hasNote && $isPaid) {
                    $ticketUrl = route('receptionist.payment.ticket', ['appointment' => $appointment->id]);
                    $ticketButton = '<a href="'.$ticketUrl.'" target="_blank" class="bg-neutral-700 text-white px-3 py-1 rounded inline-flex">Ticket</a>';
                } else {
                    $ticketButton = '<button type="button" class="bg-neutral-300 text-neutral-600 px-3 py-1 rounded cursor-not-allowed" disabled>Ticket</button>';
                }

                if ($isPaid) {
                    $payButton = '<button type="button" class="bg-neutral-300 text-neutral-600 px-3 py-1 rounded cursor-not-allowed" disabled>Pagado</button>';

                    return '<div class="flex gap-2 flex-wrap">'.$detailButton.$ticketButton.$payButton.'</div>';
                }

                if ($allCovered) {
                    $payButton = '<button type="button" class="bg-neutral-300 text-neutral-600 px-3 py-1 rounded cursor-not-allowed" disabled>Servicios cubiertos</button>';

                    return '<div class="flex gap-2 flex-wrap">'.$detailButton.$ticketButton.$payButton.'</div>';
                }

                if (!$isCompleted) {
                    $payButton = '<button type="button" class="bg-neutral-300 text-neutral-600 px-3 py-1 rounded cursor-not-allowed" disabled>Liquidar</button>';

                    return '<div class="flex gap-2 flex-wrap">'.$detailButton.$ticketButton.$payButton.'</div>';
                }

                $url = route('receptionist.payment', ['appointment' => $appointment->id]);
                $payButton = '<a href="'.$url.'" class="bg-teal-600 text-white px-3 py-1 rounded inline-flex">Liquidar</a>';

                return '<div class="flex gap-2 flex-wrap">'.$detailButton.$ticketButton.$payButton.'</div>';
            });
    }

    public function columns(): array
    {
        return [
            Column::make('ID', 'id'),

            Column::make('Fecha', 'date_formatted', 'date')
                ->sortable(),

            Column::make('Hora', 'time_formatted', 'time')
                ->sortable(),

            Column::make('Paciente', 'patient_name', 'patients.name')
                ->searchable()
                ->sortable(),

            Column::make('Membresia', 'membership_number', 'patient_policies.number')
                ->searchable()
                ->sortable()
                ->hidden(isHidden: true, isForceHidden: false),

            Column::make('Medico', 'doctor_name', 'doctor_users.name')
                ->searchable()
                ->sortable(),

            Column::make('Estado', 'status_badge', 'status')
                ->searchable()
                ->sortable(),

            Column::make('Pago', 'payment_status_badge', 'user_payment')
                ->sortable(),

            Column::make('Monto', 'amount_formatted', 'user_payment')
                ->sortable(),

            Column::make('Accion', 'payment_button'),
        ];
    }

}

