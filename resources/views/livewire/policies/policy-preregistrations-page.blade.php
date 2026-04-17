<div>
    <div wire:init="maybeOpenPrefilledPreregistrationModal"></div>

    <x-slot name="header">
        {{ __('app.preregistration') }}
    </x-slot>

    <div>
        <x-ui.card size="full">
            <x-ui.heading class="flex items-center justify-between mb-4" level="h3" size="sm">
                <span>Catalogo de preregistros</span>

                <x-ui.modal.trigger id="preregistration-modal" wire:click="resetPreregistrationForm">
                    <x-ui.button color="teal" icon="paper-airplane">
                        Nuevo preregistro
                    </x-ui.button>
                </x-ui.modal.trigger>
            </x-ui.heading>
            <p>Administra invitaciones de preregistro, seguimiento y cancelacion.</p>
        </x-ui.card>
    </div>

    @if ($lastPreregistrationUrl)
        <div class="pt-2">
            <x-ui.card size="full">
                <x-ui.heading level="h3" size="sm">
                    Ultima invitacion de preregistro
                </x-ui.heading>

                <p class="text-sm mt-2">
                    Teléfono: <span class="font-semibold">{{ $lastPreregistrationPhone }}</span>
                    | Referencia: <span class="font-semibold">{{ $lastPreregistrationReference }}</span>
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
            <x-ui.heading level="h3" size="sm">
                Preregistros
            </x-ui.heading>

            <div class="pt-4 grid gap-2 md:grid-cols-5">
                <x-ui.field>
                    <x-ui.label>Filtro teléfono</x-ui.label>
                    <x-ui.input wire:model.live.debounce.400ms="filterPreregistrationPhone" placeholder="Buscar teléfono..." />
                </x-ui.field>

                <x-ui.field>
                    <x-ui.label>Filtro plan</x-ui.label>
                    <x-ui.select wire:model.live="filterPreregistrationPlan" placeholder="Todas">
                        <x-ui.select.option value="">Todas</x-ui.select.option>
                        @foreach($preregistrationFilterPlans as $plan)
                            <x-ui.select.option value="{{ $plan->id }}">
                                {{ $plan->name }}
                            </x-ui.select.option>
                        @endforeach
                    </x-ui.select>
                </x-ui.field>

                <x-ui.field>
                    <x-ui.label>Filtro tipo</x-ui.label>
                        <x-ui.select wire:model.live="filterPreregistrationType" placeholder="Todos">
                            <x-ui.select.option value="">Todos</x-ui.select.option>
                        <x-ui.select.option value="individual_policy">Membresía individual</x-ui.select.option>
                        <x-ui.select.option value="group_owner">Titular colectiva</x-ui.select.option>
                        <x-ui.select.option value="group_member">Miembro colectiva</x-ui.select.option>
                    </x-ui.select>
                </x-ui.field>

                <x-ui.field>
                    <x-ui.label>Filtro estatus</x-ui.label>
                    <x-ui.select wire:model.live="filterPreregistrationStatus" placeholder="Todos">
                        <x-ui.select.option value="">Todos</x-ui.select.option>
                        <x-ui.select.option value="active">Pendiente</x-ui.select.option>
                        <x-ui.select.option value="expired">Expirado</x-ui.select.option>
                        <x-ui.select.option value="used">Registrado</x-ui.select.option>
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
                            <th class="text-left px-3 py-2 font-semibold">Teléfono</th>
                            <th class="text-left px-3 py-2 font-semibold">Tipo</th>
                            <th class="text-left px-3 py-2 font-semibold">Plan / Colectivo</th>
                            <th class="text-left px-3 py-2 font-semibold">Membresía padre</th>
                            <th class="text-left px-3 py-2 font-semibold">Promotor</th>
                            <th class="text-left px-3 py-2 font-semibold">Estatus</th>
                            <th class="text-left px-3 py-2 font-semibold">Vigencia</th>
                            <th class="text-left px-3 py-2 font-semibold">Membresía creada</th>
                            <th class="text-left px-3 py-2 font-semibold">Opciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($preregistrations as $preregistration)
                            <tr class="border-t border-neutral-200 dark:border-neutral-700">
                                <td class="px-3 py-2">{{ $preregistration->phone }}</td>
                                <td class="px-3 py-2">{{ $preregistration->type_label }}</td>
                                <td class="px-3 py-2">{{ $preregistration->company_name ?: $preregistration->plan?->name ?: '-' }}</td>
                                <td class="px-3 py-2">{{ $preregistration->parentPolicy?->number ?: '-' }}</td>
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
                                <td colspan="9" class="px-3 py-4 text-center text-neutral-600 dark:text-neutral-300">
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
        id="preregistration-modal"
        wire:ignore.self
        animation="fade"
        width="2xl"
        heading="{{ $preregistrationId ? 'Editar preregistro' : 'Nuevo preregistro' }}"
        description="Captura el teléfono y el plan para enviar la invitacion de registro"
        x-on:close-preregistration-modal.window="$data.close()"
        x-on:open-preregistration-modal.window="$data.open()"
    >
        <form wire:submit="savePreregistration">
            <x-ui.fieldset label="Datos del preregistro">
                <x-ui.field required>
                    <x-ui.label>Tipo de preregistro</x-ui.label>
                    <x-ui.select wire:model.live="preregistrationType">
                        <x-ui.select.option value="individual_policy">Membresía individual</x-ui.select.option>
                        <x-ui.select.option value="group_owner">Titular de membresía colectiva</x-ui.select.option>
                        <x-ui.select.option value="group_member">Miembro de membresía colectiva</x-ui.select.option>
                    </x-ui.select>
                    <x-ui.error name="preregistrationType" />
                </x-ui.field>

                <x-ui.field required>
                    <x-ui.label>Teléfono</x-ui.label>
                    <x-ui.input wire:model="preregistrationPhone" name="preregistrationPhone" placeholder="3300000000" />
                    <x-ui.error name="preregistrationPhone" />
                </x-ui.field>

                @if($preregistrationType === 'group_owner')
                    <div wire:key="preregistration-group-owner-fields">
                        <x-ui.field required>
                            <x-ui.label>Plan</x-ui.label>
                            <x-ui.select
                                wire:model="preregistrationPlan"
                                placeholder="Selecciona un plan colectivo"
                                searchable
                            >
                                @foreach($preregistrationGroupPlans as $plan)
                                    <x-ui.select.option value="{{ $plan->id }}">
                                        {{ $plan->name }}
                                    </x-ui.select.option>
                                @endforeach
                            </x-ui.select>
                            <x-ui.error name="preregistrationPlan" />
                        </x-ui.field>

                        <x-ui.field required>
                            <x-ui.label>Nombre del colectivo</x-ui.label>
                            <x-ui.input wire:model="preregistrationCompanyName" placeholder="Inmax" />
                            <x-ui.error name="preregistrationCompanyName" />
                        </x-ui.field>

                        <x-ui.field required>
                            <x-ui.label>Tipo de persona</x-ui.label>
                            <x-ui.select wire:model="preregistrationCompanyType">
                                <x-ui.select.option value="PF">Persona fisica</x-ui.select.option>
                                <x-ui.select.option value="PM">Persona moral</x-ui.select.option>
                                <x-ui.select.option value="PFA">Persona fisica con actividad empresarial</x-ui.select.option>
                            </x-ui.select>
                            <x-ui.error name="preregistrationCompanyType" />
                        </x-ui.field>

                        <x-ui.field required>
                            <x-ui.label>Razon social</x-ui.label>
                            <x-ui.input wire:model="preregistrationCompanyLegalName" placeholder="Inmax SA de CV" />
                            <x-ui.error name="preregistrationCompanyLegalName" />
                        </x-ui.field>

                        <x-ui.field required>
                            <x-ui.label>RFC</x-ui.label>
                            <x-ui.input wire:model="preregistrationCompanyRfc" placeholder="XAXX010101111" maxlength="13" />
                            <x-ui.error name="preregistrationCompanyRfc" />
                        </x-ui.field>

                        <x-ui.field required>
                            <x-ui.label>Cantidad de miembros</x-ui.label>
                            <x-ui.input wire:model="preregistrationMembers" type="number" min="1" max="99" />
                            <x-ui.error name="preregistrationMembers" />
                        </x-ui.field>
                    </div>
                @elseif($preregistrationType === 'group_member')
                    <div wire:key="preregistration-group-member-fields">
                        <x-ui.field required>
                            <x-ui.label>Membresía colectiva</x-ui.label>
                            <x-ui.select
                                wire:key="preregistration-group-policy-select"
                                wire:model.live="preregistrationParentPolicy"
                                placeholder="Selecciona una membresía colectiva"
                                searchable
                            >
                                @foreach($preregistrationGroupPolicies as $policy)
                                    <x-ui.select.option value="{{ $policy->id }}">
                                        {{ $policy->number }} - {{ $policy->user->name }} - {{ $policy->user->company?->name }}
                                    </x-ui.select.option>
                                @endforeach
                            </x-ui.select>
                            <x-ui.error name="preregistrationParentPolicy" />
                        </x-ui.field>

                        <x-ui.field>
                            <x-ui.label>Plan</x-ui.label>
                            <x-ui.input :value="$this->selectedGroupPolicy?->plan?->name ?: 'Selecciona una membresía colectiva'" readonly copyable="false" />
                        </x-ui.field>

                        @if($this->selectedGroupPolicyCapacity)
                            <div class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-950">
                                <p class="font-semibold">
                                    Cupo de la membresía {{ $this->selectedGroupPolicy?->number }}
                                </p>
                                <p class="mt-2">
                                    Total: {{ $this->selectedGroupPolicyCapacity['total_slots'] }}
                                    | Registrados: {{ $this->selectedGroupPolicyCapacity['registered_members'] }}
                                    | Preregistros: {{ $this->selectedGroupPolicyCapacity['pending_preregistrations'] }}
                                    | Disponibles: {{ $this->selectedGroupPolicyCapacity['available_slots'] }}
                                </p>
                            </div>
                        @endif
                    </div>
                @else
                    <div wire:key="preregistration-individual-fields">
                        <x-ui.field required>
                            <x-ui.label>Plan</x-ui.label>
                            <x-ui.select
                                wire:key="preregistration-individual-plan-select"
                                wire:model="preregistrationPlan"
                                placeholder="Selecciona un plan"
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
                            <x-ui.label>Membresía principal</x-ui.label>
                            <x-ui.select
                                wire:key="preregistration-individual-parent-policy-select"
                                wire:model="preregistrationParentPolicy"
                                placeholder="Sin membresía principal"
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
                    </div>
                @endif

                @if(auth()->user()?->profile === 'Sales')
                    <x-ui.field>
                        <x-ui.label>Promotor</x-ui.label>
                        <x-ui.input :value="auth()->user()->name" readonly copyable="false" />
                    </x-ui.field>
                @else
                    <x-ui.field required>
                        <x-ui.label>Promotor</x-ui.label>
                        <x-ui.select
                            wire:model="preregistrationSalesUser"
                            placeholder="Selecciona un promotor"
                            searchable
                        >
                            @foreach($preregistrationSalesAgents as $agent)
                                <x-ui.select.option value="{{ $agent->id }}">
                                    {{ $agent->name }}
                                </x-ui.select.option>
                            @endforeach
                        </x-ui.select>
                        <x-ui.error name="preregistrationSalesUser" />
                    </x-ui.field>
                @endif
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
                Teléfono del preregistro: <span class="font-semibold">{{ $preregistrationToCancelPhone }}</span>
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
</div>
