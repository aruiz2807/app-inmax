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
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('name', fn ($model) => e($model->user->name))
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

            Column::make('Correo', 'email')
                ->searchable()
                ->hidden(isHidden: true, isForceHidden: false),

            Column::make('Telefono', 'phone'),

            Column::make('Especialidad', 'specialty')
                ->sortable(),

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
                ->slot('Editar')
                ->id()
                ->class('bg-teal-600 text-white px-3 py-1 rounded')
                ->dispatch('editDoctor', ['doctorId' => $row->id])
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
