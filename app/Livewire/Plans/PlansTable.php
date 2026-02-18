<?php

namespace App\Livewire\Plans;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Number;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;

final class PlansTable extends PowerGridComponent
{
    public string $tableName = 'plansTable';

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
        return Plan::query();
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('name')
            ->add('price')
            ->add('type')
            ->add('type_formatted', fn ($model) => $model->type === 'Group' ? 'Colectiva' : 'Individual')
            ->add('status')
            ->add('status_toggle', fn ($model) => $model->status === 'Active')
            ->add('created_at')
            ->add('price_in_mxn', fn ($dish) => Number::currency($dish->price, in: 'MXN', locale: 'es_MX'))
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

            Column::make('Precio', 'price_in_mxn', 'price')
                ->sortable(),

            Column::make('Tipo', 'type_formatted', 'type')
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

    public function actions(Plan $row): array
    {
        return [
            Button::add('edit')
                ->slot('Editar')
                ->id()
                ->class('bg-teal-600 text-white px-3 py-1 rounded')
                ->dispatch('editPlan', ['planId' => $row->id]),

            Button::add('editBenefits')
                ->slot('Beneficios')
                ->id()
                ->class('bg-teal-600 text-white px-3 py-1 rounded')
                ->dispatch('editBenefits', ['planId' => $row->id]),
        ];
    }

    public function onUpdatedToggleable($id, $field, $value): void
    {
        $plan = Plan::find($id);

        if ($field === 'status_toggle') {
            $plan->status = $value ? 'Active' : 'Inactive';
            $plan->save();
        }
    }
}
