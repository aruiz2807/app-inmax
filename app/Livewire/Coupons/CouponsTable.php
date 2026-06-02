<?php

namespace App\Livewire\Coupons;

use App\Models\Coupon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;

final class CouponsTable extends PowerGridComponent
{
    public string $tableName = 'couponsTable';

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
        return Coupon::query();
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
            ->add('type')
            ->add('type_formatted', fn ($model) => $model->type === 'Amount' ? 'Importe' : 'Porcentaje')
            ->add('value')
            ->add('limit_min')
            ->add('limit_max')
            ->add('status')
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

            Column::make('Tipo', 'type_formatted', 'type')
                ->sortable(),

            Column::make('Valor', 'value')
                ->sortable(),

            Column::make('Limite inferior', 'limit_min')
                ->sortable(),

            Column::make('Limite superior', 'limit_max')
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

    public function actions(Coupon $row): array
    {
        return [
            Button::add('edit')
                ->slot(Blade::render('<div class="flex items-center gap-2"><x-ui.icon name="pencil-square" variant="outline" class="w-5 h-5"/><span>Editar</span></div>'))
                ->id()
                ->class('text-teal-600 hover:bg-teal-50 px-2 py-1 rounded transition-colors')
                ->dispatch('editCoupon', ['couponId' => $row->id]),

            Button::add('doctors')
                ->slot(Blade::render('<div class="flex items-center gap-2"><x-ui.icon name="pencil-square" variant="outline" class="w-5 h-5"/><span>Red</span></div>'))
                ->id()
                ->class('text-cyan-600 hover:bg-cyan-50 px-2 py-1 rounded transition-colors')
                ->dispatch('editDoctors', ['couponId' => $row->id]),
        ];
    }

    public function onUpdatedToggleable($id, $field, $value): void
    {
        $coupon = Coupon::find($id);

        if ($field === 'status_toggle') {
            $coupon->status = $value ? 'Active' : 'Inactive';
            $coupon->save();
        }
    }
}
