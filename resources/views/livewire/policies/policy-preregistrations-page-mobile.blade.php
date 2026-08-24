<div>
    <div wire:init="maybeOpenPrefilledPreregistrationModal"></div>

    <x-slot name="header">
        {{ __('app.preregistration') }}
    </x-slot>

    <div class="relative w-full">
        <x-ui.card size="full">
            <x-ui.heading class="flex justify-center" level="h3" size="sm">
                <x-ui.icon name="paper-airplane" class="self-center" />
                <x-ui.text class="text-lg ml-2">Catalogo de preregistros</x-ui.text>
            </x-ui.heading>

            <x-ui.text class="mt-2 mb-4 text-sm opacity-50 text-center">
                Administra invitaciones de preregistro, seguimiento y cancelacion.
            </x-ui.text>

            <x-ui.modal.trigger id="preregistration-modal" wire:click="resetPreregistrationForm">
                <x-ui.button color="teal" icon="paper-airplane" class="w-full">
                    Nuevo preregistro
                </x-ui.button>
            </x-ui.modal.trigger>
        </x-ui.card>
    </div>

    @if ($lastPreregistrationUrl)
        <div class="relative w-full pt-2">
            <x-ui.card size="full">
                <x-ui.heading class="flex justify-center" level="h3" size="sm">
                    <x-ui.icon name="paper-airplane" class="self-center" />
                    <x-ui.text class="text-lg ml-2">Ultima invitacion de preregistro</x-ui.text>
                </x-ui.heading>

                <x-ui.text class="mt-2 text-sm text-center">
                    Teléfono: <span class="font-semibold">{{ $lastPreregistrationPhone }}</span>
                    | Referencia: <span class="font-semibold">{{ $lastPreregistrationReference }}</span>
                </x-ui.text>

                <x-ui.text class="mt-1 mb-2 text-sm opacity-50 text-center">
                    Vigencia: {{ $lastPreregistrationExpiresAt }}
                </x-ui.text>

                <a href="{{ $lastPreregistrationUrl }}" class="ui-link block break-all text-center" target="_blank" rel="noopener noreferrer">
                    {{ $lastPreregistrationUrl }}
                </a>
            </x-ui.card>
        </div>
    @endif

    <div class="relative w-full pt-2">
        <x-ui.card size="full">
            @if($preregistrations->isEmpty())
                <div class="flex justify-center p-4 bg-[#FFFFFF] rounded-2xl shadow-sm hover:shadow-md transition-shadow border border-white/50">
                    <x-ui.text class="text-base text-center">No hay preregistros con los filtros actuales.</x-ui.text>
                </div>
            @endif

            @foreach ($preregistrations as $preregistration)
                <div class="flex flex-col p-2 mb-4 bg-[#FFFFFF] rounded-2xl shadow-sm hover:shadow-md transition-shadow border border-white/50">

                    <div class="flex justify-center mb-2 gap-x-2">
                        <x-ui.badge :icon="$preregistration->status_icon" variant="outline" :color="$preregistration->status_color" pill>
                            {{ $preregistration->status_label }}
                        </x-ui.badge>
                    </div>

                    <div class="flex flex-col px-2">
                        <div class="flex items-center justify-between">
                            <x-ui.text class="text-lg font-semibold">{{ $preregistration->phone }}</x-ui.text>
                            <x-ui.text class="text-sm opacity-50">{{ $preregistration->type_label }}</x-ui.text>
                        </div>

                        <x-ui.text class="text-base mt-2">
                            {{ $preregistration->company_name ?: $preregistration->plan?->name ?: '-' }}
                        </x-ui.text>

                        @if (! $preregistration->isGroupMember())
                            <x-ui.text class="text-sm opacity-50">
                                Membresía padre: {{ $preregistration->parentPolicy?->number ?: '-' }}
                            </x-ui.text>
                        @endif

                        <x-ui.text class="text-sm opacity-50">
                            Promotor: {{ $preregistration->salesUser?->name }}
                        </x-ui.text>

                        <x-ui.text class="text-sm opacity-50">
                            Vigencia: {{ $preregistration->expires_at?->format('d/m/Y H:i') }}
                        </x-ui.text>

                        <x-ui.text class="text-sm opacity-50">
                            Membresía creada: {{ $preregistration->policy?->number ?: '-' }}
                        </x-ui.text>
                    </div>

                    @if ($preregistration->canBeManaged())
                        <x-ui.separator class="mt-2 mb-2"/>

                        <div class="flex justify-center">
                            <x-ui.button class="w-40 mr-1" wire:click="editPreregistration({{ $preregistration->id }})" variant="outline" color="teal" icon="pencil-square">
                                Editar
                            </x-ui.button>

                            <x-ui.button class="w-40 ml-1" wire:click="promptPreregistrationCancellation({{ $preregistration->id }})" variant="outline" color="red" icon="x-circle">
                                Cancelar
                            </x-ui.button>
                        </div>
                    @endif
                </div>
            @endforeach

            <div class="pt-2">
                {{ $preregistrations->links() }}
            </div>
        </x-ui.card>
    </div>

    @include('livewire.policies.partials.preregistration-modals')
</div>
