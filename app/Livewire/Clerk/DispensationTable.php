<?php

namespace App\Livewire\Clerk;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Blade;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class DispensationTable extends PowerGridComponent
{
    public string $tableName = 'dispensationTable';

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

    public function datasource(): Collection
    {
        return collect(DispensationPage::appointmentsDataset())
            ->filter(fn (array $appointment): bool => (bool) ($appointment['has_prescription'] ?? false))
            ->sortByDesc('appointment_at')
            ->values();
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('patient_name')
            ->add('patient_display', function ($row): string {
                return Blade::render(
                    '<div class="flex items-center gap-2"><x-ui.avatar size="sm" icon="user" color="teal" :src="$photo" circle /><span class="font-medium">{{ $name }}</span></div>',
                    [
                        'photo' => data_get($row, 'patient_photo_url'),
                        'name' => data_get($row, 'patient_name'),
                    ]
                );
            })
            ->add('membership_number')
            ->add('prescriber_doctor')
            ->add('prescriber_doctor_display', function ($row): string {
                $rating = max(0, min(5, (int) data_get($row, 'prescriber_doctor_rating', 0)));
                $stars = str_repeat('★', $rating).str_repeat('☆', 5 - $rating);

                return Blade::render(
                    '<div class="flex items-start gap-2"><div class="pt-0.5"><x-ui.avatar size="sm" icon="user" color="teal" :src="$photo" circle /></div><div class="leading-tight"><p class="font-medium">{{ $name }}</p><p class="text-yellow-500 text-xs mt-1">{{ $stars }}</p></div></div>',
                    [
                        'photo' => data_get($row, 'prescriber_doctor_photo_url'),
                        'name' => data_get($row, 'prescriber_doctor'),
                        'stars' => $stars,
                    ]
                );
            })
            ->add('appointment_at')
            ->add('appointment_at_formatted', fn ($row): string => Carbon::parse((string) data_get($row, 'appointment_at'))->format('d/m/Y H:i'))
            ->add('status_label', fn ($row): string => data_get($row, 'is_dispensed') ? 'Surtida' : 'Pendiente')
            ->add('status_badge', function ($row): string {
                $isDispensed = (bool) data_get($row, 'is_dispensed');

                return $isDispensed
                    ? Blade::render('<x-ui.badge variant="outline" color="green" pill>Surtida</x-ui.badge>')
                    : Blade::render('<x-ui.badge variant="outline" color="yellow" pill>Pendiente</x-ui.badge>');
            });
    }

    public function columns(): array
    {
        return [
            Column::make('Nombre paciente', 'patient_display', 'patient_name')
                ->searchable()
                ->sortable(),

            Column::make('No. Membresía', 'membership_number')
                ->searchable()
                ->sortable(),

            Column::make('Médico prescriptor', 'prescriber_doctor_display', 'prescriber_doctor')
                ->searchable()
                ->sortable(),

            Column::make('Fecha consulta', 'appointment_at_formatted', 'appointment_at')
                ->sortable(),

            Column::make('Estatus', 'status_badge', 'status_label')
                ->sortable(),

            Column::action('Opciones'),
        ];
    }

    public function filters(): array
    {
        return [
            Filter::select('status_label', 'status_label')
                ->dataSource([
                    ['id' => 'Pendiente', 'name' => 'Pendiente'],
                    ['id' => 'Surtida', 'name' => 'Surtida'],
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
                ->dispatch('showDispensationDetails', ['appointmentId' => (int) data_get($row, 'id')]),
        ];
    }
}
