<?php

namespace App\Livewire\Appointments;

use App\Models\Appointment;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Blade;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\Facades\Rule;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;

final class AppointmentsTable extends PowerGridComponent
{
    public string $tableName = 'appointmentsTable';

    public function setUp(): array
    {
        $this->showCheckBox();

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
        return Appointment::query()->with(['user:id,name', 'doctor.user:id,name', 'doctor.specialty:id,name,service_id', 'doctor.specialty.service:id,name']);
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('user_id')
            ->add('user_name', fn ($model) => e($model->user->name))
            ->add('doctor_id')
            ->add('doctor_name', fn ($model) => e($model->doctor->user->name))
            ->add('specialty', fn ($model) => e($model->doctor->specialty->name))
            ->add('service', fn ($model) => e($model->doctor->specialty->service->name))
            ->add('date_formatted', fn ($model) => $model->date?->format('d/m/Y'))
            ->add('time')
            ->add('time_formatted', fn ($model) => $model->time?->format('H:i A'))
            ->add('covered', fn ($model) => Blade::render('<x-status-badge status="' . $model->covered . '" />'))
            ->add('status', fn ($model) => Blade::render('<x-status-badge status="' . $model->status . '" />'))
            ->add('created_at');
    }

    public function columns(): array
    {
        return [
            Column::make('Id', 'id'),

            Column::make('Asegurado', 'user_name')
                ->sortable(),

            Column::make('Medico', 'doctor_name')
                ->sortable(),

            Column::make('Especialidad', 'specialty')
                ->sortable()
                ->hidden(isHidden: true, isForceHidden: false),

            Column::make('Servicio', 'service')
                ->sortable(),

            Column::make('Fecha', 'date_formatted', 'date')
                ->sortable(),

            Column::make('Hora', 'time_formatted', 'time')
                ->sortable()
                ->searchable(),

            Column::make('Cubierta', 'covered')
                ->sortable()
                ->searchable(),

            Column::make('Estatus', 'status')
                ->sortable()
                ->searchable(),

            Column::make('Fecha registro', 'created_at_formatted', 'created_at')
                ->sortable()
                ->hidden(isHidden: true, isForceHidden: false),

            Column::action('Action')
        ];
    }

    public function filters(): array
    {
        return [
            Filter::datepicker('date'),
        ];
    }

    #[\Livewire\Attributes\On('edit')]
    public function edit($rowId): void
    {
        $this->js('alert('.$rowId.')');
    }

    public function actions(Appointment $row): array
    {
        return [
            Button::add('edit')
                ->slot('Editar')
                ->id()
                ->class('w-22 bg-teal-600 text-white px-3 py-1 rounded')
                ->dispatch('editAppointment', ['appointmentId' => $row->id]),

            Button::add('cancel')
                ->slot('Cancelar')
                ->id()
                ->class('w-22 bg-orange-600 text-white px-3 py-1 rounded')
                ->dispatch('cancelAppointment', ['appointmentId' => $row->id]),
        ];
    }

    public function actionRules(): array
    {

        return [

            Rule::button('edit')
                ->when(fn($model) => $model->status === 'Completed' || $model->status === 'Cancelled' || $model->status === 'No-show')
                ->hide(),

            Rule::button('cancel')
                ->when(fn($model) => $model->status === 'Completed' || $model->status === 'Cancelled' || $model->status === 'No-show')
                ->hide(),

        ];
    }
}
