<x-ui.card size="full">
    <x-application-logo class="block h-12 w-auto" />

    <x-ui.text class="mt-8 text-2xl font-semibold text-neutral-900">
        Hola {{ auth()->user()->name }}!
    </x-ui.text>

    <x-ui.text class="mt-2 text-lg mb-8">
        Bienvenido a la consola de administración de Inmax-Sure
    </x-ui.text>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-12">
        <div class="p-6 bg-white border border-neutral-200 rounded-xl shadow-sm">
            <livewire:home.charts.policies-by-month-chart />
        </div>
        <div class="p-6 bg-white border border-neutral-200 rounded-xl shadow-sm">
            <livewire:home.charts.policies-by-seller-chart />
        </div>
    </div>
</x-ui.card>
