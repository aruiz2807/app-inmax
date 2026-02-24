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
use PowerComponents\LivewirePowerGrid\Facades\Rule;
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
        return Policy::query()->with(['plan:id,name,type', 'sales_user:id,name', 'user:id,name,company_id', 'user.company:id,name']);
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
            ->add('sales_user_id')
            ->add('sales_agent', fn ($model) => e($model->sales_user->name))
            ->add('company', fn ($model) => e($model->user->company->name ?? ''))
            ->add('plan_id')
            ->add('plan_name', fn ($model) => e($model->plan->name))
            ->add('parent_policy_id')
            ->add('number')
            ->add('start_date_formatted', fn ($model) => $model->start_date?->format('d/m/Y'))
            ->add('end_date_formatted', fn ($model) => $model->end_date?->format('d/m/Y'))
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

            Column::make('Propietario', 'name')
                ->sortable(),

            Column::make('Empresa', 'company')
                ->sortable()
                ->hidden(isHidden: true, isForceHidden: false),

            Column::make('Cobertura', 'plan_name')
                ->sortable()
                ->hidden(isHidden: true, isForceHidden: false),

            Column::make('Comienza', 'start_date_formatted', 'start_date')
                ->sortable(),

            Column::make('Finaliza', 'end_date_formatted', 'end_date')
                ->sortable(),

            Column::make('Promotor', 'sales_agent')
                ->sortable()
                ->hidden(isHidden: true, isForceHidden: false),

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

    public function actions(Policy $row): array
    {
        return [
            Button::add('edit')
                ->slot('Editar')
                ->id()
                ->class('w-22 bg-teal-600 text-white px-3 py-1 rounded')
                ->dispatch('editPolicy', ['policyId' => $row->id]),

            Button::add('activate')
                ->slot('Activar')
                ->id()
                ->class('w-22 bg-teal-600 text-white px-3 py-1 rounded')
                ->dispatch('activatePolicy', ['policyId' => $row->id]),

            Button::add('inactive')
                ->slot('Inactivar')
                ->id()
                ->class('w-22 bg-gray-600 text-white px-3 py-1 rounded')
                ->dispatch('deactivatePolicy', ['policyId' => $row->id]),

            Button::add('cancel')
                ->slot('Cancelar')
                ->id()
                ->class('w-22 bg-orange-600 text-white px-3 py-1 rounded')
                ->dispatch('cancelPolicy', ['policyId' => $row->id]),

            Button::add('members')
                ->slot('Miembro')
                ->id()
                ->class('w-24 bg-teal-600 text-white px-3 py-1 rounded')
                ->dispatch('addMember', ['policyId' => $row->id]),
        ];
    }

    public function actionRules(): array
    {
        return [

            Rule::button('activate')
                ->when(fn($model) => $model->status === 'Active' || $model->status === 'Cancelled')
                ->hide(),

            Rule::button('inactive')
                ->when(fn($model) => $model->status === 'Inactive' || $model->status === 'Cancelled')
                ->hide(),

            Rule::button('cancel')
                ->when(fn($model) => $model->status === 'Cancelled')
                ->hide(),

            Rule::button('members')
                ->when(fn($model) => $model->type !== 'Group' || $model->parent_policy_id)
                ->hide(),

        ];
    }
}
