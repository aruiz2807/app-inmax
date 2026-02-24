<div class="max-w-md mx-auto bg-white min-h-screen overflow-hidden font-sans">
    <div class="relative w-full">
        <img src="/img/top.png" alt="Header" class="w-full object-cover">
    </div>

    <div class="grid grid-cols-[2rem_auto] justify-stretch items-center pt-4 pb-4">
        <x-ui.icon name="arrow-left" class="w-5 h-5 cursor-pointer" x-on:click="window.history.back()" />
        <x-ui.text class="text-2xl">Expediente medico</x-ui.text>
    </div>

    <div class="relative w-full">
        <x-ui.card size="full">
            <x-ui.accordion>
                <x-ui.accordion.item expanded trigger="Consultas">
                    <p>Comnsultas....</p>
                </x-ui.accordion.item>
                <x-ui.accordion.item trigger="Diagnosticos y tratamientos">
                    <p>Diagnosticos....</p>
                </x-ui.accordion.item>
                <x-ui.accordion.item trigger="Examenes">
                    <p>Examenes...</p>
                </x-ui.accordion.item>
            </x-ui.accordion>
        </x-ui.card>
    </div>
</div>
