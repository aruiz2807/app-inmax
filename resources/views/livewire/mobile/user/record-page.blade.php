<div class="max-w-md mx-auto bg-white min-h-screen overflow-hidden font-sans">
    <div class="relative w-full">
        <img src="/img/top.png" alt="Header" class="w-full object-cover">
    </div>

    <div class="grid grid-cols-[2rem_auto] justify-stretch items-center pt-4 pb-4">
        <x-ui.icon name="arrow-left" class="w-5 h-5 cursor-pointer" x-on:click="window.history.back()" />
        <x-ui.text class="text-2xl">Expediente medico</x-ui.text>
    </div>

    <div class="relative w-full">

        <x-ui.alerts variant="info" icon="information-circle">
            <x-ui.alerts.description>
                Información actualizada: <strong> {{now()->format('d/m/Y')}} </strong>
            </x-ui.alerts.description>
        </x-ui.alerts>

        <x-ui.card size="full" class="mt-4">
            <x-ui.accordion>
                <x-ui.accordion.item expanded trigger="Consultas">
                    @empty($appointments)
                    <div class="flex justify-center p-4 bg-[#FFFFFF] rounded-2xl shadow-sm hover:shadow-md transition-shadow border border-white/50">
                        <x-ui.text class="text-base">No hay consultas</x-ui.text>
                    </div>
                    @endempty
                </x-ui.accordion.item>

                <x-ui.accordion.item trigger="Diagnosticos y tratamientos">
                    @empty($diagnoses)
                    <div class="flex justify-center p-4 bg-[#FFFFFF] rounded-2xl shadow-sm hover:shadow-md transition-shadow border border-white/50">
                        <x-ui.text class="text-base">No hay diagnosticos</x-ui.text>
                    </div>
                    @endempty
                </x-ui.accordion.item>

                <x-ui.accordion.item trigger="Diagnosticos y tratamientos">
                    @empty($exams)
                    <div class="flex justify-center p-4 bg-[#FFFFFF] rounded-2xl shadow-sm hover:shadow-md transition-shadow border border-white/50">
                        <x-ui.text class="text-base">No hay diagnosticos</x-ui.text>
                    </div>
                    @endempty
                </x-ui.accordion.item>

                <x-ui.accordion.item trigger="Medicamentos">
                    @empty($medications)
                    <div class="flex justify-center p-4 bg-[#FFFFFF] rounded-2xl shadow-sm hover:shadow-md transition-shadow border border-white/50">
                        <x-ui.text class="text-base">No hay medicamentos</x-ui.text>
                    </div>
                    @endempty
                </x-ui.accordion.item>
            </x-ui.accordion>
        </x-ui.card>
    </div>
</div>
