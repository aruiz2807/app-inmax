<div class="max-w-md mx-auto bg-white min-h-screen overflow-hidden font-sans">
    <div class="relative w-full">
        <img src="/img/top.png" alt="Header" class="w-full object-cover">
    </div>

    <div class="grid grid-cols-[2rem_auto] justify-stretch items-center pt-4 pb-4">
        <x-ui.icon name="arrow-left" class="w-5 h-5 cursor-pointer" x-on:click="window.history.back()" />
        <x-ui.text class="text-2xl">Mi uso del seguro</x-ui.text>
    </div>

    <div class="relative w-full">
        <x-ui.card size="full">
            <x-ui.heading class="flex justify-center" level="h3" size="sm">
                <x-ui.icon name="shield-check" class="self-center" />
                <x-ui.text class="text-lg ml-2">Uso de la poliza</x-ui.text>
            </x-ui.heading>

            <x-ui.slider
                wire:model="percentage"
                handleVariant="circle"
                class="pointer-events-none"
                tooltips
                :fill-track="[true, false]"
                x-init="$slider.formatTooltipUsing((value) => value.toFixed() + '%')"
            />

            <x-ui.text class="mt-2 mb-4 text-sm opacity-50 text-center">
                {{ $total_used }} de {{ $total_included }} servicios utilizados
            </x-ui.text>

            <a href="#" class="w-full mt-4 flex flex-col bg-[#E3F2FD] rounded-xl shadow-sm hover:shadow-md transition-shadow border border-white/50">
            @foreach ($services as $service)
                <div class="grid grid-cols-[auto_6rem_4rem] justify-stretch items-center p-4">
                    <x-ui.text>{{ $service->service->name }}</x-ui.text>
                    <x-ui.text class="opacity-50">{{ $service->used }} de {{ $service->included }} usados</x-ui.text>
                    <x-ui.icon :name="$service->level" :class="'justify-self-end '.$service->color" />
                </div>
                <x-ui.separator />
            @endforeach
            </a>
        </x-ui.card>

        <x-ui.card size="full" class="mt-4">
            <x-ui.heading class="flex justify-center" level="h3" size="sm">
                <x-ui.icon name="information-circle" class="self-center" />
                <x-ui.text class="text-lg ml-2">Servicios fuera del plan</x-ui.text>
            </x-ui.heading>

            <x-ui.text class="mt-4 mb-2  text-base text-center">
                {{ $total_extra }} servicios utilizados
            </x-ui.text>
        </x-ui.card>
    </div>
</div>
