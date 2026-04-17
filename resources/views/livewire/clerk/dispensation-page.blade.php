<div>
    <x-slot name="header">
        Dispensación
    </x-slot>

    <x-ui.card size="full">
        <x-ui.heading class="flex items-center justify-between mb-4" level="h3" size="sm">
            <span>Dispensación de recetas</span>
        </x-ui.heading>

        <p>Registros simulados de appointments con receta, basados en la bandera de surtido.</p>

        <div class="pt-4">
            <livewire:clerk.dispensation-table />
        </div>
    </x-ui.card>

    @include('livewire.clerk.dispensation-details-modal')
</div>
