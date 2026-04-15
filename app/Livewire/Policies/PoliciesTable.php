<?php

namespace App\Livewire\Policies;

use App\Models\Policy;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\Facades\Rule;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\Traits\WithExport;  
use PowerComponents\LivewirePowerGrid\Components\SetUp\Exportable; 


final class PoliciesTable extends PowerGridComponent
{
    use WithExport; 

    public string $tableName = 'policiesTable';

    public function setUp(): array
    {
        $this->showCheckBox();

        return [
            PowerGrid::exportable(fileName: 'Membresias') 
                ->type(Exportable::TYPE_XLS, Exportable::TYPE_CSV), 
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
        $policies = null;
        $user = Auth::user();

        if($user->profile === 'Sales')
        {
            $policies = Policy::query()
                ->with(['plan:id,name,type', 'sales_user:id,name', 'user:id,name,company_id,phone,profile_photo_path', 'user.company:id,name'])
                ->where('sales_user_id', $user->id);
        }
        else
        {
            $policies = Policy::query()->with(['plan:id,name,type', 'sales_user:id,name', 'user:id,name,company_id,phone,profile_photo_path', 'user.company:id,name']);
        }

         return $policies;
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
            ->add('phone', fn ($model) => e($model->user->phone))
            ->add('photo', fn ($model) => $model->user->profile_photo_path ? url(Storage::url($model->user->profile_photo_path)) : '')
            ->add('type')
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
            Column::make('Id', 'id')
                ->visibleInExport(false),

            Column::make('Number', 'number')
                ->sortable()
                ->searchable()
                ->visibleInExport(false),

            Column::make('Propietario', 'name')
                ->sortable()
                ->visibleInExport(false),

            Column::make('Empresa', 'company')
                ->sortable()
                ->hidden(isHidden: true, isForceHidden: false)
                ->visibleInExport(false),

            Column::make('Cobertura', 'plan_name')
                ->sortable()
                ->hidden(isHidden: true, isForceHidden: false)
                ->visibleInExport(false),

            Column::make('Comienza', 'start_date_formatted', 'start_date')
                ->sortable()
                ->visibleInExport(false),

            Column::make('Finaliza', 'end_date_formatted', 'end_date')
                ->sortable()
                ->visibleInExport(false),

            Column::make('Promotor', 'sales_agent')
                ->sortable()
                ->hidden(isHidden: true, isForceHidden: false)
                ->visibleInExport(false),

            Column::make('Estatus', 'status')
                ->visibleInExport(false)
                ->visibleInExport(false),

            Column::make('Fecha registro', 'created_at_formatted', 'created_at')
                ->sortable()
                ->hidden(isHidden: true, isForceHidden: false)
                ->visibleInExport(false),

            Column::action('Opciones')
                ->visibleInExport(false),

            // Export columns
            Column::make('Id', 'id')
                ->hidden(isHidden: true, isForceHidden: true)
                ->visibleInExport(true),

            Column::make('No membresia', 'number')
                ->hidden(isHidden: true, isForceHidden: true)
                ->visibleInExport(true),
            
            Column::make('Nombre', 'name')
                ->hidden(isHidden: true, isForceHidden: true)
                ->visibleInExport(true),
            
            Column::make('Fecha inicio', 'start_date_formatted', 'start_date')
                ->hidden(isHidden: true, isForceHidden: true)
                ->visibleInExport(true), 
            
            Column::make('Fecha fin', 'end_date_formatted', 'end_date')
                ->hidden(isHidden: true, isForceHidden: true)
                ->visibleInExport(true),

            Column::make('Cel', 'phone')
                ->hidden(isHidden: true, isForceHidden: true)
                ->visibleInExport(true),

            Column::make('Tipo de membresía', 'type')
                ->hidden(isHidden: true, isForceHidden: true)
                ->visibleInExport(true),
            
            Column::make('Plan', 'plan_name')
                ->hidden(isHidden: true, isForceHidden: true)
                ->visibleInExport(true),
            
            Column::make('Foto', 'photo')
                ->hidden(isHidden: true, isForceHidden: true)
                ->visibleInExport(true),
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

        if(Auth::user()->profile === 'Sales')
        {
            return [

                Rule::button('activate')
                    ->when(fn($model) => $model->status === 'Active' || $model->status === 'Cancelled')
                    ->hide(),

                Rule::button('inactive')
                    ->when(fn($model) => true)
                    ->hide(),

                Rule::button('cancel')
                    ->when(fn($model) => true)
                    ->hide(),

                Rule::button('members')
                    ->when(fn($model) => $model->type !== 'Group' || $model->parent_policy_id)
                    ->hide(),

            ];
        }

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
