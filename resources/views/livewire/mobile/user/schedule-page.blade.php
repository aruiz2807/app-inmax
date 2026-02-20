<div class="max-w-md mx-auto bg-white min-h-screen overflow-hidden font-sans">
    <div class="relative w-full">
        <img src="/img/top.png" alt="Header" class="w-full object-cover">
    </div>

    <div class="grid grid-cols-[2rem_auto] justify-stretch items-center pt-4 pb-4">
        <x-ui.icon name="arrow-left" class="w-5 h-5 cursor-pointer" x-on:click="window.history.back()" />
        <x-ui.text class="text-2xl">Programar consulta</x-ui.text>
    </div>

    <div class="relative w-full">
        <x-ui.card size="full">
            <div class="flex items-center p-4 bg-[#E3F2FD] rounded-2xl shadow-sm hover:shadow-md transition-shadow border border-white/50">
                <div class="p-3 bg-[#2D4356] rounded-xl text-white mr-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#FFFFFF" viewBox="0 0 256 256">
                        <path d="M220,160a12,12,0,1,1-12-12A12,12,0,0,1,220,160Zm-4.55,39.29A48.08,48.08,0,0,1,168,240H144a48.05,48.05,0,0,1-48-48V151.49A64,64,0,0,1,40,88V40a8,8,0,0,1,8-8H72a8,8,0,0,1,0,16H56V88a48,48,0,0,0,48.64,48c26.11-.34,47.36-22.25,47.36-48.83V48H136a8,8,0,0,1,0-16h24a8,8,0,0,1,8,8V87.17c0,32.84-24.53,60.29-56,64.31V192a32,32,0,0,0,32,32h24a32.06,32.06,0,0,0,31.22-25,40,40,0,1,1,16.23.27ZM232,160a24,24,0,1,0-24,24A24,24,0,0,0,232,160Z"></path>
                    </svg>
                </div>
                <div>
                    <x-ui.text class="text-lg">Medico general</x-ui.text>
                    @if($isIncluded)
                    <x-ui.text class="text-sm opacity-50">Consulta cubierta en su plan</x-ui.text>
                    @else
                    <x-ui.text class="text-sm opacity-50">Consulta con costo preferencial</x-ui.text>
                    @endif
                </div>
                @if($isIncluded)
                <x-ui.badge class="ml-8" icon="check-circle" variant="outline" color="green" pill>Cubierta</x-ui.badge>
                @else
                <x-ui.badge class="ml-8" icon="exclamation-triangle" variant="outline" color="yellow" pill>Adicional</x-ui.badge>
                @endif
            </div>
        </x-ui.card>

        <x-ui.card size="full" class="mt-4">
            <x-ui.heading class="flex justify-center" level="h3" size="sm">
                <x-ui.icon name="calendar" variant="solid" class="self-center" />
                <x-ui.text class="text-lg ml-2">Fechas disponibles</x-ui.text>
            </x-ui.heading>

            <div class="grid grid-cols-3 sm:grid-cols-5 gap-3 mt-4">
                @foreach($availableDates as $date)
                    <label class="group relative cursor-pointer">
                        <input type="radio"
                            wire:model.live="selectedDate"
                            value="{{ $date['id'] }}"
                            class="sr-only peer"
                        >
                        <div class="flex flex-col items-center justify-center py-4 border-2 rounded-xl transition-all duration-200
                                    bg-white border-slate-100
                                    peer-checked:bg-sky-200 peer-checked:border-sky-200 peer-checked:shadow-lg peer-checked:shadow-blue-200
                                    hover:border-sky-100">

                            <span class="text-[10px] font-bold uppercase tracking-tighter mb-1
                                        text-slate-400 peer-checked:text-blue-200">
                                {{ $date['day'] }}
                            </span>

                            <span class="text-sm font-extrabold text-slate-700 peer-checked:text-white">
                                {{ $date['num'] }} {{ $date['month'] }}
                            </span>
                        </div>
                    </label>
                @endforeach
            </div>
        </x-ui.card>

        <x-ui.card size="full" class="mt-4">
            <x-ui.heading class="flex justify-center" level="h3" size="sm">
                <x-ui.icon name="clock" variant="solid" class="self-center" />
                <x-ui.text class="text-lg ml-2">Horarios disponibles</x-ui.text>
                <div wire:loading wire:target="selectedDate" class="ml-4">
                    <svg class="animate-spin h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
            </x-ui.heading>


            <div class="grid grid-cols-3 sm:grid-cols-4 gap-3 mt-4">
                @foreach($availableHours as $hour)
                    <label class="group relative cursor-pointer">
                        <input type="radio"
                            wire:model.live="selectedTime"
                            value="{{ $hour['id'] }}"
                            class="sr-only peer"
                        >
                        <div class="flex flex-col items-center justify-center py-4 border-2 rounded-xl transition-all duration-200
                                    bg-white border-slate-100
                                    peer-checked:bg-sky-200 peer-checked:border-sky-200 peer-checked:shadow-lg peer-checked:shadow-blue-200
                                    hover:border-sky-100">

                            <span class="text-sm font-extrabold text-slate-700 peer-checked:text-white">
                                {{ $hour['time'] }}
                            </span>
                        </div>
                    </label>
                @endforeach
            </div>
        </x-ui.card>

        <div class="flex justify-center mt-8 mb-8">
            <x-ui.button wire:click="schedule" icon="check" color="teal">Programar consulta</x-ui.button>
        </div>
    </div>
</div>
