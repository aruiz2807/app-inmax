<x-ui.card size="full">
    <x-ui.text class="text-2xl font-semibold text-neutral-900">
        Hola {{ auth()->user()->name }}
    </x-ui.text>

    <x-ui.text class="mt-2 text-lg text-neutral-600">
        Panel de {{ __('app.clerk') }}
    </x-ui.text>

    <div class="mt-6 rounded-xl border border-dashed border-neutral-300 p-6">
        <x-ui.text class="text-neutral-600">
            Este dashboard esta listo, de momento sin informacion.
        </x-ui.text>
    </div>
</x-ui.card>
