<?php

namespace App\Livewire\Dispatcher;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class TransportsTable extends PowerGridComponent
{
    public string $tableName = 'dispatcherTransportsTable';
    public string $tab = 'booked';
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
        $doctorIds = Auth::user()->staffDoctors()->pluck('doctors.id');

        return Appointment::query()
            ->select('appointments.*')
            ->leftJoin('users as patients', 'patients.id', '=', 'appointments.user_id')
            ->leftJoin('doctors', 'doctors.id', '=', 'appointments.doctor_id')
            ->leftJoin('users as doctor_users', 'doctor_users.id', '=', 'doctors.user_id')
            ->leftJoin('policies as patient_policies', 'patient_policies.user_id', '=', 'appointments.user_id')
            ->with([
                'user:id,name',
                'user.policy:id,user_id,number',
                'doctor:id,user_id',
                'doctor.user:id,name',
                'office:id,name',
            ])
            ->whereNotNull('appointments.origin_address')
            ->whereIn('appointments.doctor_id', $doctorIds)
            ->when($this->tab === 'booked', fn (Builder $query) => $query->where('appointments.status', AppointmentStatus::REQUESTED))
            ->when(in_array($this->tab, ['in_progress', 'inProgress'], true), fn (Builder $query) => $query->where('appointments.status', AppointmentStatus::BOOKED))
            ->when($this->tab === 'completed', fn (Builder $query) => $query->where('appointments.status', AppointmentStatus::COMPLETED))
            ->when($this->tab === 'cancelled', fn (Builder $query) => $query->whereIn('appointments.status', [
                AppointmentStatus::CANCELLED,
                AppointmentStatus::REJECTED,
                AppointmentStatus::NO_SHOW,
            ]));
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
            str_contains($normalized, 'program') => AppointmentStatus::REQUESTED->value,
            str_contains($normalized, 'rechaz') => AppointmentStatus::REJECTED->value,
            str_contains($normalized, 'agend') => AppointmentStatus::BOOKED->value,
            str_contains($normalized, 'progres') => AppointmentStatus::BOOKED->value,
            str_contains($normalized, 'cancel') => AppointmentStatus::CANCELLED->value,
            str_contains($normalized, 'atendid') => AppointmentStatus::COMPLETED->value,
            str_contains($normalized, 'no se present') || str_contains($normalized, 'no-show') || str_contains($normalized, 'noshow') => AppointmentStatus::NO_SHOW->value,
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
            ->add('origin_address', fn (Appointment $appointment) => e($appointment->origin_address ?? '-'))
            ->add('destination_address', fn (Appointment $appointment) => e($appointment->destination_address ?? '-'))
            ->add('status_badge', fn (Appointment $appointment) => $this->renderStatusBadge($appointment));
    }

    private function renderStatusBadge(Appointment $appointment): string
    {
        $status = $appointment->status?->value;
        $statusBadge = Blade::render('<x-status-badge status="'.$status.'" />');

        if ($status === AppointmentStatus::REQUESTED->value) {
            $statusBadge = '<span class="px-2 py-1 text-xs font-bold rounded-full text-violet-700 bg-violet-100">Programado</span>';
        }

        if ($status === AppointmentStatus::BOOKED->value) {
            $statusBadge = '<span class="px-2 py-1 text-xs font-bold rounded-full text-blue-700 bg-blue-100">En progreso</span>';
        }

        if ($status === AppointmentStatus::COMPLETED->value) {
            $statusBadge = '<span class="px-2 py-1 text-xs font-bold rounded-full text-green-700 bg-green-100">Finalizado</span>';
        }

        if (! (bool) $appointment->edited) {
            return $statusBadge;
        }

        return '<div class="flex flex-col items-start gap-1">'
            .$statusBadge
            .'<span class="px-2 py-1 text-xs font-bold rounded-full text-amber-700 bg-amber-100">Editado</span>'
            .'</div>';
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

            Column::make('Origen', 'origin_address', 'appointments.origin_address')
                ->searchable()
                ->sortable(),

            Column::make('Destino', 'destination_address', 'appointments.destination_address')
                ->searchable()
                ->sortable(),

            Column::make('Estado', 'status_badge', 'status')
                ->searchable()
                ->sortable(),

            Column::action('Opciones'),
        ];
    }

    public function actions(Appointment $row)
    {
        $status = $row->status instanceof AppointmentStatus
            ? $row->status
            : AppointmentStatus::tryFrom((string) $row->status);

        $isCompleted = $status === AppointmentStatus::COMPLETED;
        $isCancelled = in_array($status, [AppointmentStatus::CANCELLED, AppointmentStatus::REJECTED, AppointmentStatus::NO_SHOW], true);

        return [
            Button::add('show')
                ->slot(Blade::render('<div class="flex items-center gap-2"><x-ui.icon name="eye" variant="outline" class="w-5 h-5"/><span>Detalle</span></div>'))
                ->id()
                ->class('text-sky-600 hover:bg-sky-50 px-2 py-1 rounded transition-colors')
                ->dispatch('dispatcherShowTransportDetail', ['appointmentId' => $row->id]),

            $isCompleted || $isCancelled
                ? Button::add('edit_disabled')
                    ->slot(Blade::render('<div class="flex items-center gap-2 opacity-40 cursor-not-allowed"><x-ui.icon name="pencil-square" variant="outline" class="w-5 h-5"/><span>Editar</span></div>'))
                    ->id()
                    ->class('text-neutral-500 px-2 py-1 rounded')
                : Button::add('edit')
                    ->slot(Blade::render('<div class="flex items-center gap-2"><x-ui.icon name="pencil-square" variant="outline" class="w-5 h-5"/><span>Editar</span></div>'))
                    ->id()
                    ->class('text-teal-600 hover:bg-teal-50 px-2 py-1 rounded transition-colors')
                    ->dispatch('dispatcherEditTransport', ['appointmentId' => $row->id]),

            $isCompleted || $isCancelled
                ? Button::add('close_disabled')
                    ->slot(Blade::render('<div class="flex items-center gap-2 opacity-40 cursor-not-allowed"><x-ui.icon name="clipboard-document-check" variant="outline" class="w-5 h-5"/><span>Cerrar</span></div>'))
                    ->id()
                    ->class('text-neutral-500 px-2 py-1 rounded')
                : Button::add('close')
                ->slot(Blade::render('<div class="flex items-center gap-2"><x-ui.icon name="clipboard-document-check" variant="outline" class="w-5 h-5"/><span>Cerrar</span></div>'))
                ->id()
                ->class('text-blue-600 hover:bg-blue-50 px-2 py-1 rounded transition-colors')
                ->dispatch('dispatcherCloseTransport', ['appointmentId' => $row->id]),

            $isCompleted || $isCancelled
                ? Button::add('cancel_disabled')
                    ->slot(Blade::render('<div class="flex items-center gap-2 opacity-40 cursor-not-allowed"><x-ui.icon name="x-circle" variant="outline" class="w-5 h-5"/><span>Cancelar</span></div>'))
                    ->id()
                    ->class('text-neutral-500 px-2 py-1 rounded')
                : Button::add('cancel')
                ->slot(Blade::render('<div class="flex items-center gap-2"><x-ui.icon name="x-circle" variant="outline" class="w-5 h-5"/><span>Cancelar</span></div>'))
                ->id()
                ->class('text-red-600 hover:bg-red-50 px-2 py-1 rounded transition-colors')
                ->dispatch('dispatcherCancelTransport', ['appointmentId' => $row->id]),
        ];
    }
}