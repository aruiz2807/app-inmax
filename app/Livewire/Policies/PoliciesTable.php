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
        return [
            'user' => [ 
                'name', 
            ],
        ];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('user_id')
            ->add('name', fn ($model) => e($model->user?->name ?? ''))
            ->add('phone', fn ($model) => e($model->user?->clean_phone ?? ''))
            ->add('photo', fn ($model) => $model->user?->profile_photo_path ? Storage::disk('public')->url($model->user->profile_photo_path) : '')
            ->add('type')
            ->add('sales_user_id')
            ->add('sales_agent', fn ($model) => e($model->sales_user?->name ?? 'N/A'))
            ->add('company', fn ($model) => e($model->user?->company?->name ?? ''))
            ->add('plan_id')
            ->add('plan_name', fn ($model) => e($model->plan?->name ?? ''))
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

            Column::make('Plan', 'plan_name')
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
        $profile = Auth::user()->profile;


        $ticketButton = Button::add('ticket')
            ->slot(
                Blade::render('<div class="flex items-center gap-2">
                    <x-ui.icon name="ticket" variant="outline" class="w-5 h-5"/>
                    <span>Ticket</span>
                </div>')
            )
            ->id()
            ->class(
                'px-2 py-1 rounded transition-colors ' .
                ($row->status === 'Inactive'
                    ? 'opacity-50 cursor-not-allowed text-gray-400'
                    : 'text-indigo-600 hover:bg-indigo-50')
            );

        if ($row->status !== 'Inactive') {
            $ticketButton->dispatch('printPolicyTicket', ['policyId' => $row->id]);
        }

        if($profile === 'Receptionist'){
            return [
                Button::add('show')
                    ->slot(Blade::render('<div class="flex items-center gap-2"><x-ui.icon name="eye" variant="outline" class="w-5 h-5"/><span>Detalle</span></div>'))
                    ->id()
                    ->class('text-sky-600 hover:bg-sky-50 px-2 py-1 rounded transition-colors')
                    ->dispatch('showStatus', ['policyId' => $row->id]),

                Button::add('activate')
                    ->slot(Blade::render('<div class="flex items-center gap-2"><x-ui.icon name="check-circle" variant="outline" class="w-5 h-5"/><span>Activar</span></div>'))
                    ->id()
                    ->class('text-teal-600 hover:bg-teal-50 px-2 py-1 rounded transition-colors')
                    ->dispatch('activatePolicy', ['policyId' => $row->id]),

                Button::add('members')
                    ->slot(Blade::render('<div class="flex items-center gap-2"><x-ui.icon name="user-group" variant="outline" class="w-5 h-5"/><span>Miembros</span></div>'))
                    ->id()
                    ->class('text-teal-600 hover:bg-teal-50 px-2 py-1 rounded transition-colors')
                    ->dispatch('addMember', ['policyId' => $row->id]),

                $ticketButton,
            ];
        }

        return [
            Button::add('show')
                ->slot(Blade::render('<div class="flex items-center gap-2"><x-ui.icon name="eye" variant="outline" class="w-5 h-5"/><span>Detalle</span></div>'))
                ->id()
                ->class('text-sky-600 hover:bg-sky-50 px-2 py-1 rounded transition-colors')
                ->dispatch('showStatus', ['policyId' => $row->id]),

            Button::add('edit')
                ->slot(Blade::render('<div class="flex items-center gap-2"><x-ui.icon name="pencil-square" variant="outline" class="w-5 h-5"/><span>Editar</span></div>'))
                ->id()
                ->class('text-teal-600 hover:bg-teal-50 px-2 py-1 rounded transition-colors')
                ->dispatch('editPolicy', ['policyId' => $row->id]),

            Button::add('activate')
                ->slot(Blade::render('<div class="flex items-center gap-2"><x-ui.icon name="check-circle" variant="outline" class="w-5 h-5"/><span>Activar</span></div>'))
                ->id()
                ->class('text-teal-600 hover:bg-teal-50 px-2 py-1 rounded transition-colors')
                ->dispatch('activatePolicy', ['policyId' => $row->id]),

            Button::add('inactive')
                ->slot(Blade::render('<div class="flex items-center gap-2"><x-ui.icon name="pause-circle" variant="outline" class="w-5 h-5"/><span>Inactivar</span></div>'))
                ->id()
                ->class('text-gray-600 hover:bg-gray-100 px-2 py-1 rounded transition-colors')
                ->dispatch('deactivatePolicy', ['policyId' => $row->id]),

            Button::add('cancel')
                ->slot(Blade::render('<div class="flex items-center gap-2"><x-ui.icon name="x-circle" variant="outline" class="w-5 h-5"/><span>Cancelar</span></div>'))
                ->id()
                ->class('text-orange-600 hover:bg-orange-50 px-2 py-1 rounded transition-colors')
                ->dispatch('cancelPolicy', ['policyId' => $row->id]),

            Button::add('members')
                ->slot(Blade::render('<div class="flex items-center gap-2"><x-ui.icon name="user-group" variant="outline" class="w-5 h-5"/><span>Miembros</span></div>'))
                ->id()
                ->class('text-teal-600 hover:bg-teal-50 px-2 py-1 rounded transition-colors')
                ->dispatch('addMember', ['policyId' => $row->id]),

            Button::add('contract')
                ->slot(Blade::render('<div class="flex items-center gap-2"><x-ui.icon name="document-text" variant="outline" class="w-5 h-5"/><span>Contrato</span></div>'))
                ->id()
                ->class('text-indigo-600 hover:bg-indigo-50 px-2 py-1 rounded transition-colors')
                ->dispatch('printPolicy', ['policyId' => $row->id]),

            $ticketButton,
        ];
    }

    public function actionRules(): array
    {

        if(Auth::user()->profile === 'Sales')
        {
            return [
                Rule::button('view')
                    ->when(fn($model) => true)
                    ->hide(),

                Rule::button('activate')
                    ->when(fn($model) => true)
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
