<?php

namespace App\Livewire\Users;

use App\Models\User;
use App\Models\UserLegalAcceptance;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
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
            ->withCount('permissions')
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
            ->add('permissions_count')
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

            Column::make('Teléfono', 'phone')
                ->searchable(),

            Column::make('Perfil', 'profile')
                ->sortable(),

            Column::make('Permisos', 'permissions_count')
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
                    ['id' => 'Clerk', 'name' => __('app.clerk')],
                    ['id' => 'Receptionist', 'name' => 'Recepcionista'],
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
                ->slot(Blade::render('<div class="flex items-center gap-2"><x-ui.icon name="pencil-square" variant="outline" class="w-5 h-5"/><span>Editar</span></div>'))
                ->id()
                ->class('text-teal-600 hover:bg-teal-50 px-2 py-1 rounded transition-colors')
                ->dispatch('editUser', ['userId' => $row->id]),

            Button::add('send_pin_link')
                ->slot(Blade::render('<div class="flex items-center gap-2"><x-ui.icon name="paper-airplane" variant="outline" class="w-5 h-5"/><span>Enviar link PIN</span></div>'))
                ->id()
                ->class('text-neutral-600 hover:bg-neutral-100 px-2 py-1 rounded transition-colors')
                ->dispatch('sendUserPinSetupLink', ['userId' => $row->id]),

            Button::add('permissions')
                ->slot(Blade::render('<div class="flex items-center gap-2"><x-ui.icon name="shield-check" variant="outline" class="w-5 h-5"/><span>Permisos</span></div>'))
                ->id()
                ->class('text-sky-600 hover:bg-sky-50 px-2 py-1 rounded transition-colors')
                ->dispatch('manageUserPermissions', ['userId' => $row->id]),
        ];
    }
}
