<?php

namespace App\Livewire\Clerk;

use App\Models\Appointment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class DispensationTable extends PowerGridComponent
{
    public string $tableName = 'dispensationTable';
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
        return Appointment::query()
            ->leftJoin('appointment_notes', 'appointment_notes.appointment_id', '=', 'appointments.id')
            ->select('appointments.*', 'appointment_notes.id as appointment_note_id')
            ->with(['user.policy', 'doctor.user'])
            ->whereNotNull('status_prescription');
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('appointment_note_id', function ($row): string {
                $appointmentNoteId = data_get($row, 'appointment_note_id');

                if (! is_numeric($appointmentNoteId)) {
                    return '-';
                }

                return str_pad((string) (int) $appointmentNoteId, 5, '0', STR_PAD_LEFT);
            })
            ->add('patient_name', fn ($row): string => data_get($row, 'user.name', 'Sin paciente'))
            ->add('patient_display', function ($row): string {
                return Blade::render(
                    '<div class="flex items-center gap-2"><x-ui.avatar size="sm" icon="user" color="teal" :src="$photo" circle /><span class="font-medium">{{ $name }}</span></div>',
                    [
                        'photo' => data_get($row, 'user.photo_url'),
                        'name' => data_get($row, 'user.name', 'Sin paciente'),
                    ]
                );
            })
            ->add('membership_status', fn ($row): string => (string) data_get($row, 'user.policy.status', 'Inactive'))
            ->add('membership_status_badge', function ($row): string {
                $status = (string) data_get($row, 'user.policy.status', 'Inactive');
                $color = $status === 'Active' ? 'green' : 'gray';
                $text = $status === 'Active' ? 'Activa' : 'Inactiva';

                return Blade::render(
                    '<x-ui.badge variant="outline" :color="$color" pill>{{ $text }}</x-ui.badge>',
                    ['color' => $color, 'text' => $text]
                );
            })
            ->add('membership_number', fn ($row): string => data_get($row, 'user.policy.number', '-'))
            ->add('prescriber_doctor', fn ($row): string => data_get($row, 'doctor.user.name', 'Sin médico'))
            ->add('prescriber_doctor_display', function ($row): string {
                $rating = max(0, min(5, (int) data_get($row, 'doctor.rating', 0)));
                $stars = str_repeat('★', $rating).str_repeat('☆', 5 - $rating);

                return Blade::render(
                    '<div class="flex items-start gap-2"><div class="pt-0.5"><x-ui.avatar size="sm" icon="user" color="teal" :src="$photo" circle /></div><div class="leading-tight"><p class="font-medium">{{ $name }}</p><p class="text-yellow-500 text-xs mt-1">{{ $stars }}</p></div></div>',
                    [
                        'photo' => data_get($row, 'doctor.user.photo_url'),
                        'name' => data_get($row, 'doctor.user.name', 'Sin médico'),
                        'stars' => $stars,
                    ]
                );
            })
            ->add('appointment_at_formatted', function ($row): string {
                $date = data_get($row, 'date');
                $time = data_get($row, 'time');

                if (! $date || ! $time) {
                    return '-';
                }

                $datePart = Carbon::parse($date)->toDateString();
                $timePart = Carbon::parse($time)->format('H:i:s');

                return Carbon::createFromFormat('Y-m-d H:i:s', $datePart.' '.$timePart)->format('d/m/Y H:i');
            })
            ->add('status_label', function ($row): string {
                return match ((string) data_get($row, 'status_prescription')) {
                    'Filled' => 'Surtida',
                    'Partial' => 'Surtida Parcial',
                    'Cancelled' => 'Vencida',
                    default => 'Pendiente',
                };
            })
            ->add('status_badge', function ($row): string {
                return match ((string) data_get($row, 'status_prescription')) {
                    'Filled' => Blade::render('<x-ui.badge variant="outline" color="green" pill>Surtida</x-ui.badge>'),
                    'Partial' => Blade::render('<x-ui.badge variant="outline" color="blue" pill>Surtida Parcial</x-ui.badge>'),
                    'Cancelled' => Blade::render('<x-ui.badge variant="outline" color="red" pill>Vencida</x-ui.badge>'),
                    default => Blade::render('<x-ui.badge variant="outline" color="yellow" pill>Pendiente</x-ui.badge>'),
                };
            });
    }

    public function columns(): array
    {
        return [
            Column::make('Receta', 'appointment_note_id', 'appointment_note_id')
                ->searchable()
                ->sortable()
                ->sortUsing(fn (Builder $query, string $direction) => $query->orderBy('appointment_notes.id', $direction)),

            Column::make('Nombre paciente', 'patient_display', 'patient_name')
                ->searchable()
                ->sortable()
                ->sortUsing(fn (Builder $query, string $direction) => $query->orderBy(
                    \App\Models\User::query()
                        ->select('name')
                        ->whereColumn('users.id', 'appointments.user_id')
                        ->limit(1),
                    $direction
                )),

            Column::make('Membresía', 'membership_status_badge', 'membership_status')
                ->sortable()
                ->sortUsing(fn (Builder $query, string $direction) => $query->orderBy(
                    \App\Models\Policy::query()
                        ->select('status')
                        ->whereColumn('policies.user_id', 'appointments.user_id')
                        ->limit(1),
                    $direction
                ))
                ->hidden(isHidden: true, isForceHidden: false),

            Column::make('No. Membresía', 'membership_number')
                ->searchable()
                ->sortable()
                ->sortUsing(fn (Builder $query, string $direction) => $query->orderBy(
                    \App\Models\Policy::query()
                        ->select('number')
                        ->whereColumn('policies.user_id', 'appointments.user_id')
                        ->limit(1),
                    $direction
                )),

            Column::make('Médico prescriptor', 'prescriber_doctor_display', 'prescriber_doctor')
                ->searchable()
                ->sortable()
                ->sortUsing(fn (Builder $query, string $direction) => $query->orderBy(
                    \App\Models\Doctor::query()
                        ->select('users.name')
                        ->join('users', 'users.id', '=', 'doctors.user_id')
                        ->whereColumn('doctors.id', 'appointments.doctor_id')
                        ->limit(1),
                    $direction
                )),

            Column::make('Fecha consulta', 'appointment_at_formatted', 'date')
                ->sortable(),

            Column::make('Estatus', 'status_badge', 'status_prescription')
                ->sortable(),

            Column::action('Opciones'),
        ];
    }

    public function filters(): array
    {
        return [
            Filter::select('status_prescription', 'status_prescription')
                ->dataSource([
                    ['id' => 'Pending', 'name' => 'Pendiente'],
                    ['id' => 'Filled', 'name' => 'Surtida'],
                    ['id' => 'Partial', 'name' => 'Surtida Parcial'],
                    ['id' => 'Cancelled', 'name' => 'Vencida'],
                ])
                ->optionValue('id')
                ->optionLabel('name'),
        ];
    }

    public function actions($row): array
    {
        return [
            Button::add('show_details')
                ->slot('Ver detalles')
                ->id()
                ->class('bg-teal-600 text-white px-3 py-1 rounded')
                ->dispatch('openPrescription', ['appointmentId' => (int) data_get($row, 'id')]),
        ];
    }
}
