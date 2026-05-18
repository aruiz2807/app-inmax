<?php

namespace App\Livewire\Doctors;

use Livewire\Livewire;
use App\Models\Doctor;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;

final class DoctorsTable extends PowerGridComponent
{
    public string $tableName = 'doctorsTable';

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
        return Doctor::query()->with(['specialty:id,name', 'user:id,name,email,phone']);
    }

    public function relationSearch(): array
    {
        return [
            'user' => [ 
                'name', 
            ],
            'specialty' => [
                'name', 
            ],
        ];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('name', fn ($model) => e($model->user->name))
            ->add('type')
            ->add('type_translated', fn ($model) => e($model->type->label()))
            ->add('email', fn ($model) => e($model->user->email))
            ->add('phone', fn ($model) => e($model->user->phone))
            ->add('specialty', fn ($model) => e($model->specialty->name))
            ->add('rating_stars', fn ($model) => Blade::render('<livewire:star-rating rate="' . $model->rating . '"/>'))
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

            Column::make('Nombre', 'name')
                ->sortable()
                ->searchable(),

            Column::make('Tipo', 'type_translated', 'type')
                ->sortable(),

            Column::make('Especialidad', 'specialty')
                ->sortable(),

            Column::make('Correo', 'email')
                ->searchable()
                ->hidden(isHidden: true, isForceHidden: false),

            Column::make('Teléfono', 'phone'),

            Column::make('Rating', 'rating_stars'),

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

    public function actions(Doctor $row): array
    {
        return [
            Button::add('edit')
                ->slot(Blade::render('<div class="flex items-center gap-2"><x-ui.icon name="pencil-square" variant="outline" class="w-5 h-5"/><span>Editar</span></div>'))
                ->id()
                ->class('text-teal-600 hover:bg-teal-50 px-2 py-1 rounded transition-colors')
                ->dispatch('editDoctor', ['doctorId' => $row->id]),

            Button::add('editServices')
                ->slot(Blade::render('<div class="flex items-center gap-2"><x-ui.icon name="clipboard-document-list" variant="outline" class="w-5 h-5"/><span>Servicios</span></div>'))
                ->id()
                ->class('text-cyan-600 hover:bg-cyan-50 px-2 py-1 rounded transition-colors')
                ->dispatch('editServices', ['doctorId' => $row->id]),

            Button::add('editCoupons')
                ->slot(Blade::render('<div class="flex items-center gap-2"><x-ui.icon name="ticket" variant="outline" class="w-5 h-5"/><span>Cupones</span></div>'))
                ->id()
                ->class('text-sky-600 hover:bg-sky-50 px-2 py-1 rounded transition-colors')
                ->dispatch('editCoupons', ['doctorId' => $row->id]),
        ];
    }

    public function onUpdatedToggleable($id, $field, $value): void
    {
        $doctor = Doctor::find($id);

        if ($field === 'status_toggle') {
            $doctor->status = $value ? 'Active' : 'Inactive';
            $doctor->save();
        }
    }
}
