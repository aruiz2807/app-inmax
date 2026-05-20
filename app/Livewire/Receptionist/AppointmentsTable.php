<?php

namespace App\Livewire\Receptionist;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use PowerComponents\LivewirePowerGrid\Button;
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
        $this->dateTo = Carbon::now()->endOfMonth()->toDateString();
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
                    ->where('appointments.status', AppointmentStatus::COMPLETED->value);
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
            'month' => [Carbon::now()->startOfMonth()->startOfDay(), Carbon::now()->endOfMonth()->endOfDay()],
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

            Column::action('Accion'),
        ];
    }

    public function actions(Appointment $row): array
    {
        $canOpenTicket = !is_null($row->user_payment) && !is_null($row->note?->id);
        $isPaid = !is_null($row->user_payment);
        $status = $row->status instanceof AppointmentStatus
            ? $row->status
            : AppointmentStatus::tryFrom((string) $row->status);
        $isCompleted = $status === AppointmentStatus::COMPLETED;

        return [
            Button::add('show')
                ->slot(Blade::render('<div class="flex items-center gap-2"><x-ui.icon name="eye" variant="outline" class="w-5 h-5"/><span>Detalle</span></div>'))
                ->id()
                ->class('text-sky-600 hover:bg-sky-50 px-2 py-1 rounded transition-colors')
                ->dispatch('showReceptionistAppointmentDetail', ['appointmentId' => $row->id]),

            $canOpenTicket
                ? Button::add('ticket')
                    ->slot(Blade::render('<a href="'.route('receptionist.payment.ticket', ['appointment' => $row->id]).'" target="_blank" class="inline-flex items-center gap-2"><x-ui.icon name="ticket" variant="outline" class="w-5 h-5"/><span>Ticket</span></a>'))
                    ->id()
                    ->class('text-neutral-700 hover:bg-neutral-100 px-2 py-1 rounded transition-colors')
                : Button::add('ticket_disabled')
                    ->slot(Blade::render('<div class="inline-flex items-center gap-2 bg-neutral-100 text-neutral-500 px-2 py-1 rounded cursor-not-allowed"><x-ui.icon name="ticket" variant="outline" class="w-5 h-5"/><span>Ticket</span></div>'))
                    ->id()
                    ->class('text-neutral-500'),

            $isPaid
                ? Button::add('paid')
                    ->slot(Blade::render('<div class="inline-flex items-center gap-2 bg-neutral-100 text-neutral-500 px-2 py-1 rounded cursor-not-allowed"><x-ui.icon name="check-circle" variant="outline" class="w-5 h-5"/><span>Pagado</span></div>'))
                    ->id()
                    ->class('text-neutral-500')
                : ($isCompleted
                    ? Button::add('settle')
                        ->slot(Blade::render('<a href="'.route('receptionist.payment', ['appointment' => $row->id]).'" class="inline-flex items-center gap-2"><x-ui.icon name="currency-dollar" variant="outline" class="w-5 h-5"/><span>Liquidar</span></a>'))
                        ->id()
                        ->class('text-teal-600 hover:bg-teal-50 px-2 py-1 rounded transition-colors')
                    : Button::add('settle_disabled')
                        ->slot(Blade::render('<div class="inline-flex items-center gap-2 bg-neutral-100 text-neutral-500 px-2 py-1 rounded cursor-not-allowed"><x-ui.icon name="currency-dollar" variant="outline" class="w-5 h-5"/><span>Liquidar</span></div>'))
                        ->id()
                        ->class('text-neutral-500')),
        ];
    }

    public function actionRules(): array
    {
        return [];
    }

}

