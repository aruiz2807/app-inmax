<div>
    <x-slot name="header">
        {{ __('app.policies') }}
    </x-slot>

    <div>
        <x-ui.card size="full">
            <x-ui.heading class="flex items-center justify-between mb-4" level="h3" size="sm">
                <span>Catalogo de polizas</span>

                <div class="flex gap-2">
                    <x-ui.modal.trigger id="preregistration-modal" wire:click="resetPreregistrationForm">
                        <x-ui.button color="gray" icon="paper-airplane">
                            Preregistro
                        </x-ui.button>
                    </x-ui.modal.trigger>

                    <x-ui.modal.trigger id="policy-modal" wire:click="resetForm">
                        <x-ui.button color="teal" icon="plus-circle">
                            Registrar poliza
                        </x-ui.button>
                    </x-ui.modal.trigger>
                </div>
            </x-ui.heading>
            <p>Resgistre y administre las polizas de los clientes</p>
        </x-ui.card>
    </div>

    @if ($lastPreregistrationUrl)
        <div class="pt-2">
            <x-ui.card size="full">
                <x-ui.heading level="h3" size="sm">
                    Ultima invitacion de preregistro
                </x-ui.heading>

                <p class="text-sm mt-2">
                    Telefono: <span class="font-semibold">{{ $lastPreregistrationPhone }}</span>
                    | Cobertura: <span class="font-semibold">{{ $lastPreregistrationPlanName }}</span>
                </p>

                <p class="text-sm mt-1 text-slate-600">
                    Vigencia: {{ $lastPreregistrationExpiresAt }}
                </p>

                <a href="{{ $lastPreregistrationUrl }}" class="ui-link block mt-2 break-all" target="_blank" rel="noopener noreferrer">
                    {{ $lastPreregistrationUrl }}
                </a>
            </x-ui.card>
        </div>
    @endif

    <div class="pt-2">
        <x-ui.card size="full">
            <livewire:policies.policies-table />
        </x-ui.card>
    </div>

    <div class="pt-2">
        <x-ui.card size="full">
            <x-ui.heading level="h3" size="sm">
                Preregistros
            </x-ui.heading>

            <div class="pt-4 grid gap-2 md:grid-cols-4">
                <x-ui.field>
                    <x-ui.label>Filtro telefono</x-ui.label>
                    <x-ui.input wire:model.live.debounce.400ms="filterPreregistrationPhone" placeholder="Buscar telefono..." />
                </x-ui.field>

                <x-ui.field>
                    <x-ui.label>Filtro cobertura</x-ui.label>
                    <x-ui.select wire:model.live="filterPreregistrationPlan" placeholder="Todas">
                        <x-ui.select.option value="">Todas</x-ui.select.option>
                        @foreach($preregistrationPlans as $plan)
                            <x-ui.select.option value="{{ $plan->id }}">
                                {{ $plan->name }}
                            </x-ui.select.option>
                        @endforeach
                    </x-ui.select>
                </x-ui.field>

                <x-ui.field>
                    <x-ui.label>Filtro estatus</x-ui.label>
                    <x-ui.select wire:model.live="filterPreregistrationStatus" placeholder="Todos">
                        <x-ui.select.option value="">Todos</x-ui.select.option>
                        <x-ui.select.option value="active">Activo</x-ui.select.option>
                        <x-ui.select.option value="expired">Vencido</x-ui.select.option>
                        <x-ui.select.option value="used">Usado</x-ui.select.option>
                        <x-ui.select.option value="cancelled">Cancelado</x-ui.select.option>
                    </x-ui.select>
                </x-ui.field>

                <x-ui.field>
                    <x-ui.label>Registros por pagina</x-ui.label>
                    <x-ui.select wire:model.live="preregistrationPerPage">
                        <x-ui.select.option value="10">10</x-ui.select.option>
                        <x-ui.select.option value="25">25</x-ui.select.option>
                        <x-ui.select.option value="50">50</x-ui.select.option>
                    </x-ui.select>
                </x-ui.field>
            </div>

            <div class="pt-3 flex justify-end">
                <x-ui.button type="button" icon="x-circle" variant="outline" wire:click="clearPreregistrationFilters">
                    Limpiar filtros
                </x-ui.button>
            </div>

            <div class="pt-4 overflow-x-auto">
                <table class="min-w-full text-sm border border-neutral-200 dark:border-neutral-700 rounded-lg overflow-hidden">
                    <thead class="bg-neutral-100 dark:bg-neutral-800">
                        <tr>
                            <th class="text-left px-3 py-2 font-semibold">Telefono</th>
                            <th class="text-left px-3 py-2 font-semibold">Cobertura</th>
                            <th class="text-left px-3 py-2 font-semibold">Promotor</th>
                            <th class="text-left px-3 py-2 font-semibold">Estatus</th>
                            <th class="text-left px-3 py-2 font-semibold">Vigencia</th>
                            <th class="text-left px-3 py-2 font-semibold">Poliza creada</th>
                            <th class="text-left px-3 py-2 font-semibold">Opciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($preregistrations as $preregistration)
                            <tr class="border-t border-neutral-200 dark:border-neutral-700">
                                <td class="px-3 py-2">{{ $preregistration->phone }}</td>
                                <td class="px-3 py-2">{{ $preregistration->plan?->name }}</td>
                                <td class="px-3 py-2">{{ $preregistration->salesUser?->name }}</td>
                                <td class="px-3 py-2">
                                    <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold bg-neutral-100 text-neutral-700">
                                        {{ $preregistration->status_label }}
                                    </span>
                                </td>
                                <td class="px-3 py-2">{{ $preregistration->expires_at?->format('d/m/Y H:i') }}</td>
                                <td class="px-3 py-2">{{ $preregistration->policy?->number ?: '-' }}</td>
                                <td class="px-3 py-2">
                                    <div class="flex flex-wrap gap-2">
                                        @if ($preregistration->canBeManaged())
                                            <x-ui.button
                                                type="button"
                                                icon="document-text"
                                                variant="outline"
                                                wire:click="editPreregistration({{ $preregistration->id }})"
                                            >
                                                Editar
                                            </x-ui.button>

                                            <x-ui.button
                                                type="button"
                                                icon="x-circle"
                                                color="rose"
                                                wire:click="promptPreregistrationCancellation({{ $preregistration->id }})"
                                            >
                                                Cancelar
                                            </x-ui.button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-3 py-4 text-center text-neutral-600 dark:text-neutral-300">
                                    No hay preregistros con los filtros actuales.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="pt-4">
                {{ $preregistrations->links() }}
            </div>
        </x-ui.card>
    </div>

    <x-ui.modal
        id="policy-modal"
        animation="fade"
        width="2xl"
        heading="{{$policyId ? 'Editar poliza' : 'Nueva poliza'}}"
        description="Ingrese la siguiente información para registrar la poliza"
        x-on:close-policy-modal.window="$data.close()"
        x-on:open-policy-modal.window="$data.open()"
    >

        @if(!$policyType)
        <div class="grid grid-cols-2 gap-4">
            <x-ui.card
                wire:click="selectType('Individual')"
                class="cursor-pointer hover:border-blue-500 transition-all group flex flex-col items-center justify-center p-6 !rounded-2xl shadow-sm"
            >
                <div class="text-slate-700 group-hover:text-blue-600 transition-colors mb-3">
                    <svg class="w-12 h-12 md:w-16 md:h-16" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <span class="text-sm md:text-lg font-bold text-slate-800">Individual</span>
            </x-ui.card>

            <x-ui.card
                wire:click="selectType('Group')"
                class="cursor-pointer hover:border-blue-500 transition-all group flex flex-col items-center justify-center p-6 !rounded-2xl shadow-sm"
            >
                <div class="text-slate-700 group-hover:text-blue-600 transition-colors mb-3">
                    <svg class="w-12 h-12 md:w-16 md:h-16" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <span class="text-sm md:text-lg font-bold text-slate-800">Colectiva</span>
            </x-ui.card>
        </div>
        @endif

        @if($policyType === 'Individual')
            <livewire:policies.individual-policy-page :policyId="$policyId" :newMember="$newMember" :key="$policyId"/>
        @endif

        @if($policyType === 'Group')
            <livewire:policies.group-policy-page :policyId="$policyId" :key="$policyId"/>
        @endif

        @if(!$policyType)
            <div class="w-full flex justify-end gap-3 pt-4">
                <x-ui.button x-on:click="$data.close();" icon="x-mark" variant="outline">
                    Cancel
                </x-ui.button>
            </div>
        @endif
    </x-ui.modal>

    <x-ui.modal
        id="preregistration-modal"
        animation="fade"
        width="2xl"
        heading="{{ $preregistrationId ? 'Editar preregistro' : 'Nuevo preregistro' }}"
        description="Captura el telefono y la cobertura para enviar la invitacion de registro"
        x-on:close-preregistration-modal.window="$data.close()"
        x-on:open-preregistration-modal.window="$data.open()"
    >
        <form wire:submit="savePreregistration">
            <x-ui.fieldset label="Datos del preregistro">
                <x-ui.field required>
                    <x-ui.label>Telefono</x-ui.label>
                    <x-ui.input wire:model="preregistrationPhone" name="preregistrationPhone" placeholder="3310203040" />
                    <x-ui.error name="preregistrationPhone" />
                </x-ui.field>

                <x-ui.field required>
                    <x-ui.label>Cobertura</x-ui.label>
                    <x-ui.select
                        wire:model="preregistrationPlan"
                        placeholder="Selecciona una cobertura"
                        searchable
                    >
                        @foreach($preregistrationPlans as $plan)
                            <x-ui.select.option value="{{ $plan->id }}">
                                {{ $plan->name }}
                            </x-ui.select.option>
                        @endforeach
                    </x-ui.select>
                    <x-ui.error name="preregistrationPlan" />
                </x-ui.field>

                <x-ui.field>
                    <x-ui.label>Poliza principal</x-ui.label>
                    <x-ui.select
                        wire:model="preregistrationParentPolicy"
                        placeholder="Sin poliza principal"
                        searchable
                    >
                        @foreach($preregistrationParentPolicies as $policy)
                            <x-ui.select.option value="{{ $policy->id }}">
                                {{ $policy->number }} - {{ $policy->user->name }} - {{ $policy->user->company?->name }}
                            </x-ui.select.option>
                        @endforeach
                    </x-ui.select>
                    <x-ui.error name="preregistrationParentPolicy" />
                </x-ui.field>

                <x-ui.field>
                    <x-ui.label>Registrado por</x-ui.label>
                    <x-ui.input :value="auth()->user()->name" readonly copyable="false" />
                </x-ui.field>
            </x-ui.fieldset>

            <div class="w-full flex justify-end gap-3 pt-4">
                <x-ui.button x-on:click="$data.close();" icon="x-mark" variant="outline">
                    Cancel
                </x-ui.button>

                <x-ui.button type="submit" icon="paper-airplane" variant="primary" color="teal">
                    {{ $preregistrationId ? 'Actualizar preregistro' : 'Enviar invitacion' }}
                </x-ui.button>
            </div>
        </form>
    </x-ui.modal>

    <x-ui.modal
        id="preregistration-cancel-modal"
        animation="fade"
        width="lg"
        heading="Cancelar preregistro"
        description="Confirma que deseas cancelar el preregistro seleccionado"
        x-on:open-preregistration-cancel-modal.window="$data.open()"
        x-on:close-preregistration-cancel-modal.window="$data.close()"
    >
        <div class="space-y-4">
            <p class="text-sm text-slate-700">
                Telefono del preregistro: <span class="font-semibold">{{ $preregistrationToCancelPhone }}</span>
            </p>

            <div class="w-full flex justify-end gap-3 pt-2">
                <x-ui.button x-on:click="$data.close();" icon="x-mark" variant="outline">
                    Volver
                </x-ui.button>

                <x-ui.button wire:click="cancelPreregistration" icon="x-circle" color="rose">
                    Confirmar cancelacion
                </x-ui.button>
            </div>
        </div>
    </x-ui.modal>

    @include('livewire.policies.activation-modal')
    @include('livewire.policies.deactivation-modal')
    @include('livewire.policies.cancel-modal')
</div>
