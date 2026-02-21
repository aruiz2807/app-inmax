<?php

namespace App\Livewire\Users;

use App\Models\User;
use App\Models\UserLegalAcceptance;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class UsersTable extends PowerGridComponent
{
    public string $tableName = 'usersTable';

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
        return User::query()
            ->select('users.*')
            ->addSelect([
                'legal_accepted_at' => UserLegalAcceptance::query()
                    ->select('accepted_at')
                    ->whereColumn('user_id', 'users.id')
                    ->orderByDesc('accepted_at')
                    ->orderByDesc('id')
                    ->limit(1),
            ]);
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
            ->add('email')
            ->add('phone')
            ->add('profile')
            ->add('pin_status', fn (User $user) => $user->pin_set_at ? 'Configurado' : 'Pendiente')
            ->add('legal_accepted_at')
            ->add('legal_accepted_at_formatted', function (User $user): string {
                if (blank($user->legal_accepted_at)) {
                    return 'Sin aceptar';
                }

                return Carbon::parse($user->legal_accepted_at)->format('d/m/Y H:i:s');
            })
            ->add('created_at')
            ->add('created_at_formatted', fn (User $user) => Carbon::parse($user->created_at)->format('d/m/Y'));
    }

    public function columns(): array
    {
        return [
            Column::make('ID', 'id'),

            Column::make('Nombre', 'name')
                ->searchable()
                ->sortable(),

            Column::make('Correo', 'email')
                ->searchable(),

            Column::make('Telefono', 'phone')
                ->searchable(),

            Column::make('Perfil', 'profile')
                ->sortable(),

            Column::make('PIN', 'pin_status')
                ->sortable(),

            Column::make('Aceptacion legal', 'legal_accepted_at_formatted', 'legal_accepted_at')
                ->sortable(),

            Column::make('Fecha registro', 'created_at_formatted', 'created_at')
                ->sortable(),

            Column::action('Opciones'),
        ];
    }

    public function filters(): array
    {
        return [
            Filter::select('profile', 'profile')
                ->dataSource([
                    ['id' => 'Admin', 'name' => 'Admin'],
                    ['id' => 'Doctor', 'name' => 'Doctor'],
                    ['id' => 'Sales', 'name' => 'Sales'],
                    ['id' => 'User', 'name' => 'User'],
                ])
                ->optionValue('id')
                ->optionLabel('name'),
        ];
    }

    public function actions(User $row): array
    {
        return [
            Button::add('edit')
                ->slot('Editar')
                ->id()
                ->class('bg-teal-600 text-white px-3 py-1 rounded')
                ->dispatch('editUser', ['userId' => $row->id]),

            Button::add('send_pin_link')
                ->slot('Enviar link PIN')
                ->id()
                ->class('bg-neutral-700 text-white px-3 py-1 rounded')
                ->dispatch('sendUserPinSetupLink', ['userId' => $row->id]),
        ];
    }
}
