<div>
    <div class="relative w-full">
        <img src="/img/top.png" alt="Header" class="w-full object-cover">
    </div>

    <div class="grid grid-cols-[2rem_auto] justify-stretch items-center pt-4 pb-4">
        <x-ui.icon name="arrow-left" class="w-5 h-5 cursor-pointer" x-on:click="window.history.back()" />
        <x-ui.text class="text-2xl">Solicitar ambulancia</x-ui.text>
    </div>

    <div class="relative w-full">
        <x-ui.card size="full" class="mx-auto">
            <x-ui.alerts color="orange" icon="exclamation-triangle" class="mb-6">
                <x-ui.alerts.heading class="font-bold text-lg">
                    ¡AVISO IMPORTANTE!
                </x-ui.alerts.heading>
                <x-ui.alerts.description class="text-sm mt-2">
                    <p class="font-bold mb-4">
                        Este servicio NO sustituye al servicio de EMERGENCIAS médicas. Si tu vida o la de alguien más está en riesgo inminente, por favor comunícate de inmediato al 911.
                    </p>
                    <p class="text-xs leading-relaxed">
                        INMAX únicamente conectará tu solicitud con un proveedor particular de ambulancias para valoración y eventual traslado en caso de requerirlo para situaciones PRIORITARIAS (urgencias menores que no comprometen la vida) o PROGRAMADAS.
                    </p>
                </x-ui.alerts.description>
            </x-ui.alerts>

            <div class="flex flex-col gap-4 mt-6">
                <!-- Red Button to call 911 -->
                <x-ui.button href="tel:911" color="red" class="w-full h-12 text-lg font-bold" icon="phone">
                    Llamar al 911
                </x-ui.button>

                <!-- Primary Color Button to call another number (set your custom number in href) -->
                <x-ui.button :href="$phone" color="teal" class="w-full h-12 text-lg font-bold" icon="phone">
                    Entiendo y solicitar
                </x-ui.button>
            </div>
        </x-ui.card>
    </div>
</div>