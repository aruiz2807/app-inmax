<?php

namespace App\Livewire\Permissions;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class PermissionsTable extends PowerGridComponent
{
    public string $tableName = 'permissionsTable';

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
        return Permission::query();
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
            ->add('group_name', fn (Permission $permission) => $permission->group_name ?: 'Sin grupo')
            ->add('is_active')
            ->add('status_label', fn (Permission $permission) => $permission->is_active ? 'Activo' : 'Inactivo')
            ->add('created_at')
            ->add('created_at_formatted', fn (Permission $permission) => Carbon::parse($permission->created_at)->format('d/m/Y'));
    }

    public function columns(): array
    {
        return [
            Column::make('ID', 'id'),

            Column::make('Codigo', 'code')
                ->searchable()
                ->sortable(),

            Column::make('Nombre', 'name')
                ->searchable()
                ->sortable(),

            Column::make('Grupo', 'group_name')
                ->searchable()
                ->sortable(),

            Column::make('Estado', 'status_label', 'is_active')
                ->sortable(),

            Column::make('Fecha registro', 'created_at_formatted', 'created_at')
                ->sortable(),

            Column::action('Opciones'),
        ];
    }

    public function filters(): array
    {
        return [
            Filter::boolean('is_active', 'is_active')
                ->label('Activo', 'Inactivo'),
        ];
    }

    public function actions(Permission $row): array
    {
        return [
            Button::add('edit')
                ->slot(Blade::render('<div class="flex items-center gap-2"><x-ui.icon name="pencil-square" variant="outline" class="w-5 h-5"/><span>Editar</span></div>'))
                ->id()
                ->class('text-teal-600 hover:bg-teal-50 px-2 py-1 rounded transition-colors')
                ->dispatch('editPermission', ['permissionId' => $row->id]),
        ];
    }
}
