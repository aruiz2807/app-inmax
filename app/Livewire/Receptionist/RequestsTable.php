<?php

namespace App\Livewire\Receptionist;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\AppointmentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class RequestsTable extends PowerGridComponent
{
    public string $tableName = 'receptionistRequestsTable';
    public string $tab = 'all';
    public string $sortField = 'date';
    public string $sortDirection = 'asc';
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
                ->includeViewOnTop('livewire.receptionist.requests-date-presets'),
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
            ->addSelect([
                'service_name' => AppointmentService::query()
                    ->select('services.name')
                    ->join('services', 'services.id', '=', 'appointment_services.service_id')
                    ->whereColumn('appointment_services.appointment_id', 'appointments.id')
                    ->orderBy('appointment_services.id')
                    ->limit(1),
            ])
            ->leftJoin('users as patients', 'patients.id', '=', 'appointments.user_id')
            ->leftJoin('doctors', 'doctors.id', '=', 'appointments.doctor_id')
            ->leftJoin('users as doctor_users', 'doctor_users.id', '=', 'doctors.user_id')
            ->leftJoin('policies as patient_policies', 'patient_policies.user_id', '=', 'appointments.user_id')
            ->with(['user:id,name', 'user.policy:id,user_id,number', 'doctor:id,user_id', 'doctor.user:id,name'])
            ->whereIn('appointments.doctor_id', $doctorIds)
            ->when($this->tab === 'pending', fn (Builder $query) => $query->where('appointments.status', AppointmentStatus::REQUESTED))
            ->when($this->tab === 'booked', fn (Builder $query) => $query->where('appointments.status', AppointmentStatus::BOOKED))
            ->when($this->tab === 'rejected', fn (Builder $query) => $query->where('appointments.status', AppointmentStatus::REJECTED))
            ->when($this->tab === 'all', fn (Builder $query) => $query->whereIn('appointments.status', [
                AppointmentStatus::REQUESTED,
                AppointmentStatus::BOOKED,
                AppointmentStatus::REJECTED,
            ]))
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
            str_contains($normalized, 'solicit') => AppointmentStatus::REQUESTED->value,
            str_contains($normalized, 'agend'), str_contains($normalized, 'acept') => AppointmentStatus::BOOKED->value,
            str_contains($normalized, 'rechaz') => AppointmentStatus::REJECTED->value,
            default => $search,
        };
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('patient_name', fn (Appointment $appointment) => e($appointment->user?->name ?? 'N/A'))
            ->add('membership_number', fn (Appointment $appointment) => e($appointment->user?->policy?->number ?? '-'))
            ->add('service_name', fn (Appointment $appointment) => e($appointment->service_name ?? 'Sin servicio'))
            ->add('doctor_name', fn (Appointment $appointment) => e($appointment->doctor?->user?->name ?? 'N/A'))
            ->add('schedule', fn (Appointment $appointment) => sprintf('%s %s', $appointment->date?->format('d/m/Y') ?? '-', $appointment->time?->format('H:i') ?? '-'))
            ->add('status_badge', fn (Appointment $appointment) => Blade::render('<x-status-badge status="'.$appointment->status?->value.'" />'));
    }

    public function columns(): array
    {
        return [
            Column::make('Miembro', 'patient_name', 'patients.name')
                ->searchable()
                ->sortable(),

            Column::make('Membresia', 'membership_number', 'patient_policies.number')
                ->searchable()
                ->sortable(),

            Column::make('Servicio', 'service_name'),

            Column::make('Doctor', 'doctor_name', 'doctor_users.name')
                ->searchable()
                ->sortable(),

            Column::make('Agenda propuesta', 'schedule', 'date')
                ->sortable(),

            Column::make('Estado', 'status_badge', 'status')
                ->searchable()
                ->sortable(),

            Column::action('Acciones'),
        ];
    }

    public function actions(Appointment $row): array
    {
        $status = $row->status instanceof AppointmentStatus
            ? $row->status
            : AppointmentStatus::tryFrom((string) $row->status);

        return [
            Button::add('show')
                ->slot(Blade::render('<div class="flex items-center gap-2"><x-ui.icon name="eye" variant="outline" class="w-5 h-5"/><span>Detalle</span></div>'))
                ->id()
                ->class('text-sky-600 hover:bg-sky-50 px-2 py-1 rounded transition-colors')
                ->dispatch('showReceptionistRequestDetail', ['appointmentId' => $row->id]),

            $status === AppointmentStatus::REQUESTED
                ? Button::add('accept')
                    ->slot(Blade::render('<div class="flex items-center gap-2"><x-ui.icon name="check" variant="outline" class="w-5 h-5"/><span>Aceptar</span></div>'))
                    ->id()
                    ->class('text-teal-600 hover:bg-teal-50 px-2 py-1 rounded transition-colors')
                    ->dispatch('acceptReceptionistRequest', ['appointmentId' => $row->id])
                : ($status === AppointmentStatus::BOOKED
                    ? Button::add('accepted_state')
                        ->slot(Blade::render('<div class="flex items-center gap-2"><x-ui.icon name="check-circle" variant="outline" class="w-5 h-5"/><span>Aceptada</span></div>'))
                        ->id()
                        ->class('text-neutral-400 px-2 py-1 rounded cursor-not-allowed')
                    : Button::add('rejected_state')
                        ->slot(Blade::render('<div class="flex items-center gap-2"><x-ui.icon name="x-circle" variant="outline" class="w-5 h-5"/><span>Rechazada</span></div>'))
                        ->id()
                        ->class('text-neutral-400 px-2 py-1 rounded cursor-not-allowed')),

            $status === AppointmentStatus::REQUESTED
                ? Button::add('reject')
                    ->slot(Blade::render('<div class="flex items-center gap-2"><x-ui.icon name="x-mark" variant="outline" class="w-5 h-5"/><span>Rechazar</span></div>'))
                    ->id()
                    ->class('text-red-600 hover:bg-red-50 px-2 py-1 rounded transition-colors')
                    ->dispatch('rejectReceptionistRequest', ['appointmentId' => $row->id])
                : Button::add('state_spacer')
                    ->slot('')
                    ->id()
                    ->class('hidden'),
        ];
    }

    public function actionRules(): array
    {
        return [];
    }
}
