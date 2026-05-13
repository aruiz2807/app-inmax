<?php

namespace App\Livewire\Receptionist;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\AppointmentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
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
            ->addSelect([
                'service_name' => AppointmentService::query()
                    ->select('services.name')
                    ->join('services', 'services.id', '=', 'appointment_services.service_id')
                    ->whereColumn('appointment_services.appointment_id', 'appointments.id')
                    ->orderBy('appointment_services.id')
                    ->limit(1),
                'medical_order_id' => AppointmentService::query()
                    ->select('appointment_services.id')
                    ->whereColumn('appointment_services.appointment_id', 'appointments.id')
                    ->whereNotNull('appointment_services.attachment_name')
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
            ->add('status_badge', fn (Appointment $appointment) => Blade::render('<x-status-badge status="'.$appointment->status?->value.'" />'))
            ->add('medical_order_link', function (Appointment $appointment): string {
                if (blank($appointment->medical_order_id)) {
                    return '<span class="inline-flex bg-neutral-200 text-neutral-500 px-3 py-1 rounded">Sin orden</span>';
                }

                $url = route('attachment.download', $appointment->medical_order_id);

                return '<a href="'.$url.'" class="inline-flex bg-neutral-700 text-white px-3 py-1 rounded" target="_blank">Ver orden</a>';
            })
            ->add('actions', function (Appointment $appointment): string {
                if ($appointment->status !== AppointmentStatus::REQUESTED) {
                    $stateLabel = $appointment->status === AppointmentStatus::BOOKED ? 'Aceptada' : 'Rechazada';

                    return '<div class="flex gap-2"><button type="button" class="bg-neutral-300 text-neutral-600 px-3 py-1 rounded cursor-not-allowed" disabled>'.$stateLabel.'</button><button type="button" class="bg-neutral-300 text-neutral-600 px-3 py-1 rounded cursor-not-allowed" disabled>Sin accion</button></div>';
                }

                $acceptButton = '<button type="button" onclick="window.dispatchEvent(new CustomEvent(\'accept-receptionist-request\', { detail: { appointmentId: '.$appointment->id.' } }))" class="bg-teal-600 text-white px-3 py-1 rounded">Aceptar</button>';
                $rejectButton = '<button type="button" onclick="window.dispatchEvent(new CustomEvent(\'reject-receptionist-request\', { detail: { appointmentId: '.$appointment->id.' } }))" class="bg-red-600 text-white px-3 py-1 rounded">Rechazar</button>';

                return '<div class="flex gap-2">'.$acceptButton.$rejectButton.'</div>';
            });
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

            Column::make('Orden medica', 'medical_order_link'),

            Column::make('Doctor', 'doctor_name', 'doctor_users.name')
                ->searchable()
                ->sortable(),

            Column::make('Agenda propuesta', 'schedule', 'date')
                ->sortable(),

            Column::make('Estado', 'status_badge', 'status')
                ->searchable()
                ->sortable(),

            Column::make('Acciones', 'actions'),
        ];
    }
}
