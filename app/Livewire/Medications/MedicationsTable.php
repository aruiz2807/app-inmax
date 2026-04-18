<?php

namespace App\Livewire\Medications;

use App\Models\Medication;
use Illuminate\Support\Carbon;
use Illuminate\Support\Number;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;

final class MedicationsTable extends PowerGridComponent
{
    public string $tableName = 'medicationsTable';

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
        return Medication::query();
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('code')
            ->add('name')
            ->add('trade_name')
            ->add('active_substance')
            ->add('lab')
            ->add('packaging')
            ->add('price_public')
            ->add('price_public_formatted', fn ($model) => Number::currency($model->price_public, in: 'MXN', locale: 'es_MX'))
            ->add('price_members')
            ->add('price_members_formatted', fn ($model) => Number::currency($model->price_members, in: 'MXN', locale: 'es_MX'))
            ->add('status')
            ->add('status_toggle', fn ($model) => $model->status === 'Active')
            ->add('created_at')
            ->add('created_at_formatted', function ($model) {
                return Carbon::parse($model->created_at)->format('d/m/Y');
        });
    }

    public function columns(): array
    {
        return [
            Column::make('Id', 'id'),

            Column::make('Código', 'code')
                ->sortable()
                ->searchable(),

            Column::make('Nombre', 'name')
                ->sortable()
                ->searchable(),

            Column::make('Nombre Comercial', 'trade_name')
                ->sortable()
                ->searchable(),

            Column::make('Sustancia Activa', 'active_substance')
                ->sortable()
                ->searchable(),

            Column::make('Laboratorio', 'lab')
                ->sortable()
                ->searchable()
                ->hidden(isHidden: true, isForceHidden: false),

            Column::make('Presentación', 'packaging')
                ->sortable()
                ->searchable()
                ->hidden(isHidden: true, isForceHidden: false),

            Column::make('Precio Público', 'price_public_formatted', 'price_public')
                ->sortable(),

            Column::make('Precio Miembros', 'price_members_formatted', 'price_members')
                ->sortable(),

            Column::make('Estatus', 'status_toggle', 'status')
                ->toggleable(),

            Column::make('Fecha registro', 'created_at_formatted', 'created_at')
                ->sortable()
                ->hidden(isHidden: true, isForceHidden: false),

            Column::action('Opciones')
        ];
    }

    public function filters(): array
    {
        return [
        ];
    }

    public function actions(Medication $row): array
    {
        return [
            Button::add('edit')
                ->slot('Editar')
                ->id()
                ->class('bg-teal-600 text-white px-3 py-1 rounded')
                ->dispatch('editMedication', ['medicationId' => $row->id]),

            Button::add('openPrescription')
                ->slot('Receta')
                ->id()
                ->class('bg-teal-600 text-white px-3 py-1 rounded')
                ->dispatch('openPrescription', ['appointment_id' => $row->id]),
        ];
    }

    public function onUpdatedToggleable($id, $field, $value): void
    {
        $medication = Medication::find($id);

        if ($field === 'status_toggle') {
            $medication->status = $value ? 'Active' : 'Inactive';
            $medication->save();
        }
    }
}
