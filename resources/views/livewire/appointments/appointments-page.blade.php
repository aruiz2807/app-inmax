<div>
    <x-slot name="header">
        {{ __('app.appointments') }}
    </x-slot>

    <div>
        <x-ui.card size="full">
            <x-ui.heading class="flex items-center justify-between mb-4" level="h3" size="sm">
                <span>Registro de citas</span>

                <x-ui.modal.trigger id="appointment-modal" wire:click="resetForm">
                    <x-ui.button color="teal" icon="plus-circle">
                        Agregar cita
                    </x-ui.button>
                </x-ui.modal.trigger>
            </x-ui.heading>

            <p>Administre los citas generadas por usuarios y la red de medicos</p>
        </x-ui.card>
    </div>

    <div class="pt-2">
        <x-ui.card size="full">
            <div class="flex gap-2 mb-4 border-b border-neutral-200">
                <button
                    type="button"
                    wire:click="setTab('booked')"
                    @class([
                        'px-4 py-2 text-sm font-medium transition-colors',
                        'border-b-2 border-teal-600 text-teal-600' => $tab === 'booked',
                        'text-neutral-600 hover:text-neutral-900' => $tab !== 'booked',
                    ])
                >
                    Agendadas
                </button>

                <button
                    type="button"
                    wire:click="setTab('completed')"
                    @class([
                        'px-4 py-2 text-sm font-medium transition-colors',
                        'border-b-2 border-teal-600 text-teal-600' => $tab === 'completed',
                        'text-neutral-600 hover:text-neutral-900' => $tab !== 'completed',
                    ])
                >
                    Atendidas
                </button>

                <button
                    type="button"
                    wire:click="setTab('cancelled')"
                    @class([
                        'px-4 py-2 text-sm font-medium transition-colors',
                        'border-b-2 border-teal-600 text-teal-600' => $tab === 'cancelled',
                        'text-neutral-600 hover:text-neutral-900' => $tab !== 'cancelled',
                    ])
                >
                    Canceladas
                </button>

                <button
                    type="button"
                    wire:click="setTab('all')"
                    @class([
                        'px-4 py-2 text-sm font-medium transition-colors',
                        'border-b-2 border-teal-600 text-teal-600' => $tab === 'all',
                        'text-neutral-600 hover:text-neutral-900' => $tab !== 'all',
                    ])
                >
                    Todas
                </button>
            </div>

            <livewire:appointments.appointments-table :tab="$tab" :key="'appointments-table-'.$tab" />
        </x-ui.card>
    </div>

    <x-ui.modal
        id="appointment-modal"
        animation="fade"
        width="2xl"
        heading="{{$appointmentId ? 'Editar cita' : 'Nueva cita'}}"
        description="Ingrese la siguiente información para agendar una cita"
        x-on:close-appointment-modal.window="$data.close()"
        x-on:open-appointment-modal.window="$data.open()"
    >
        <livewire:appointments.appointment-form-page :appointmentId="$appointmentId" :key="$appointmentId"/>
    </x-ui.modal>

    @include('livewire.appointments.cancel-modal')
    @include('livewire.appointments.history-modal')
</div>
