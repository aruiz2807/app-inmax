<?php

namespace App\Livewire\Offices;

use Livewire\Livewire;
use App\Models\Office;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;

final class OfficesTable extends PowerGridComponent
{
    public string $tableName = 'officesTable';

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
        return Office::query();
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('name', fn ($model) => e($model->name))
            ->add('address')
            ->add('maps_url')
            ->add('created_at')
            ->add('created_at_formatted', function ($model) {
                return Carbon::parse($model->created_at)->format('d/m/Y');
        });
    }

    public function columns(): array
    {
        return [
            Column::make('Id', 'id'),

            Column::make('Nombre', 'name')
                ->sortable()
                ->searchable(),

            Column::make('Dirección', 'address')
                ->sortable()
                ->searchable(),

            Column::make('Google Maps', 'maps_url')
                ->sortable()
                ->searchable(),

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

    public function actions(Office $row): array
    {
        return [
            Button::add('edit')
                ->slot('Editar')
                ->id()
                ->class('bg-teal-600 text-white px-3 py-1 rounded')
                ->dispatch('editOffice', ['officeId' => $row->id])
        ];
    }

    public function onUpdatedToggleable($id, $field, $value): void
    {
        $office = Office::find($id);
    }
}
