<?php

namespace App\Livewire\Policies;

use App\Models\Policy;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;

final class PoliciesTable extends PowerGridComponent
{
    public string $tableName = 'policiesTable';

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
        return Policy::query()->with(['plan:id,name', 'user:id,name']);
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
            ->add('name', fn ($model) => e($model->user->name))
            ->add('plan_id')
            ->add('parent_policy_id')
            ->add('number')
            ->add('start_date_formatted', fn ($model) => $model->start_date?->format('d/m/Y'))
            ->add('end_date_formatted', fn ($model) => $model->start_date?->format('d/m/Y'))
            ->add('status', fn ($model) => Blade::render('<x-status-badge status="' . $model->status . '" />'))
            ->add('created_at')->add('created_at_formatted', function ($model) {
                return Carbon::parse($model->created_at)->format('d/m/Y');
        });
    }

    public function columns(): array
    {
        return [
            Column::make('Id', 'id'),

            Column::make('Number', 'number')
                ->sortable()
                ->searchable(),

            Column::make('Nombre', 'name')
                ->sortable()
                ->searchable(),

            Column::make('Start date', 'start_date_formatted', 'start_date')
                ->sortable(),

            Column::make('End date', 'end_date_formatted', 'end_date')
                ->sortable(),

            Column::make('Estatus', 'status'),

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

    #[\Livewire\Attributes\On('edit')]
    public function edit($rowId): void
    {
        $this->js('alert('.$rowId.')');
    }

    public function actions(Policy $row): array
    {
        return [
            Button::add('edit')
                ->slot('Editar')
                ->id()
                ->class('bg-teal-600 text-white px-3 py-1 rounded')
                ->dispatch('editPolicy', ['policyId' => $row->id])
        ];
    }
}
