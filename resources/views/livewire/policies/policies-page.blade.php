<div>
    <x-slot name="header">
        {{ __('app.policies') }}
    </x-slot>

    <div>
        <x-ui.card size="full">
            <x-ui.heading class="flex items-center justify-between mb-4" level="h3" size="sm">
                <span>Catalogo de polizas</span>

                <x-ui.modal.trigger id="policy-modal" wire:click="resetForm">
                    <x-ui.button color="teal" icon="plus-circle">
                        Registrar poliza
                    </x-ui.button>
                </x-ui.modal.trigger>
            </x-ui.heading>
            <p>Resgistre y administre las polizas de los clientes</p>
        </x-ui.card>
    </div>

    <div class="pt-2">
        <x-ui.card size="full">
            <livewire:policies.policies-table />
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
            <livewire:policies.individual-policy-page :policyId="$policyId" :key="$policyId"/>
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
</div>
