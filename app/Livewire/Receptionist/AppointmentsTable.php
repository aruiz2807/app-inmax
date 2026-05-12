<?php

namespace App\Livewire\Receptionist;

use App\Models\Appointment;
use Illuminate\Database\Eloquent\Builder;
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

    public function setUp(): array
    {
        return [
            PowerGrid::header()
                ->showSearchInput()
                ->showToggleColumns(),
            PowerGrid::footer()
                ->showPerPage()
                ->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        $this->tab = request()->query('tab', 'all');

        $doctorIds = Auth::user()->staffDoctors()->pluck('doctors.id');

        return Appointment::query()
            ->select('appointments.*')
            ->leftJoin('users as patients', 'patients.id', '=', 'appointments.user_id')
            ->leftJoin('doctors', 'doctors.id', '=', 'appointments.doctor_id')
            ->leftJoin('users as doctor_users', 'doctor_users.id', '=', 'doctors.user_id')
            ->with(['user:id,name', 'doctor:id,user_id', 'doctor.user:id,name'])
            ->whereIn('doctor_id', $doctorIds)
            ->when($this->tab === 'pending', fn (Builder $query) => $query->where(function (Builder $pendingQuery) {
                $pendingQuery
                    ->whereNull('user_payment');
            }))
            ->when($this->tab === 'paid', fn (Builder $query) => $query->whereNotNull('user_payment'));
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
            ->add('doctor_name', fn (Appointment $appointment) => e($appointment->doctor?->user?->name ?? 'N/A'))
            ->add('status_badge', fn (Appointment $appointment) => Blade::render('<x-status-badge status="'.$appointment->status?->value.'" />'))
            ->add('payment_status_badge', function (Appointment $appointment): string {
                if (is_null($appointment->user_payment)) {
                    return Blade::render('<x-ui.badge variant="outline" color="yellow" pill>Pendiente</x-ui.badge>');
                }

                return Blade::render('<x-ui.badge variant="outline" color="green" pill>Pagado</x-ui.badge>');
            })
            ->add('amount_formatted', fn (Appointment $appointment) => '$'.number_format((float) $appointment->user_payment, 2))
            ->add('payment_button', function (Appointment $appointment): string {
                $isPaid = !is_null($appointment->user_payment);
                $isCompleted = $appointment->status === 'Completed';

                if ($isPaid) {
                    return '<button type="button" class="bg-neutral-300 text-neutral-600 px-3 py-1 rounded cursor-not-allowed" disabled>Pagado</button>';
                }

                if (!$isCompleted) {
                    return '<button type="button" class="bg-neutral-300 text-neutral-600 px-3 py-1 rounded cursor-not-allowed" disabled>Ir a pago</button>';
                }

                $url = route('receptionist.payment', ['appointment' => $appointment->id]);

                return '<a href="'.$url.'" class="bg-teal-600 text-white px-3 py-1 rounded inline-flex">Ir a pago</a>';
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

