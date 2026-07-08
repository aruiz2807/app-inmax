<?php

namespace App\Livewire\Clerk;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class SuppliersTable extends PowerGridComponent
{
    public string $tableName = 'suppliersTable';

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
        return Supplier::query();
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
            ->add('rfc')
            ->add('address')
            ->add('phone')
            ->add('email')
            ->add('created_at')
            ->add('created_at_formatted', fn (Supplier $supplier) => Carbon::parse($supplier->created_at)->format('d/m/Y'));
    }

    public function columns(): array
    {
        return [
            Column::make('ID', 'id'),

            Column::make('Nombre', 'name')
                ->searchable()
                ->sortable(),

            Column::make('RFC', 'rfc')
                ->searchable()
                ->sortable(),

            Column::make('Telefono', 'phone')
                ->searchable()
                ->sortable(),

            Column::make('Correo', 'email')
                ->searchable()
                ->sortable(),

            Column::make('Direccion', 'address')
                ->searchable()
                ->hidden(isHidden: true, isForceHidden: false),

            Column::make('Fecha registro', 'created_at_formatted', 'created_at')
                ->sortable(),

            Column::action('Opciones'),
        ];
    }

    public function filters(): array
    {
        return [
            Filter::inputText('name')->operators(['contains']),
            Filter::inputText('rfc')->operators(['contains']),
            Filter::inputText('email')->operators(['contains']),
        ];
    }

    public function actions(Supplier $row): array
    {
        return [
            Button::add('edit')
                ->slot(Blade::render('<div class="flex items-center gap-2"><x-ui.icon name="pencil-square" variant="outline" class="w-5 h-5"/><span>Editar</span></div>'))
                ->id()
                ->class('text-teal-600 hover:bg-teal-50 px-2 py-1 rounded transition-colors')
                ->dispatch('editSupplier', ['supplierId' => $row->id]),

            Button::add('delete')
                ->slot(Blade::render('<div class="flex items-center gap-2"><x-ui.icon name="trash" variant="outline" class="w-5 h-5"/><span>Eliminar</span></div>'))
                ->id()
                ->class('text-red-600 hover:bg-red-50 px-2 py-1 rounded transition-colors')
                ->dispatch('deleteSupplier', ['supplierId' => $row->id]),
        ];
    }
}
