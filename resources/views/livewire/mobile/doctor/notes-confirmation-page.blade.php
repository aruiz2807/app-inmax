<div class="max-w-md mx-auto bg-white min-h-screen overflow-hidden font-sans">
    <div class="relative w-full">
        <img src="/img/top.png" alt="Header" class="w-full object-cover">
    </div>

    <div class="grid grid-cols-[2rem_auto] justify-stretch items-center pt-4 pb-4">
        <a href="{{ route('doctor.home') }}">
            <x-ui.icon name="arrow-left" class="w-5 h-5 cursor-pointer" />
        </a>
        <x-ui.text class="text-2xl">Nota medica</x-ui.text>
    </div>

    <div class="relative w-full">
        <x-ui.card size="full">
            <x-ui.heading class="flex justify-center" level="h3" size="sm">
                <x-ui.icon name="calendar" variant="solid" class="self-center" />
                <x-ui.text class="text-lg ml-2">Consulta finalizada!</x-ui.text>
            </x-ui.heading>

            <div class="flex justify-center mt-10 mb-10">
                <x-ui.icon name="check-circle" variant="solid" class="fill-teal-500 size-16"/>
            </div>

            <a href="#" class="flex flex-col bg-[#E3F2FD] rounded-xl shadow-sm hover:shadow-md transition-shadow border border-white/50">
                <div class="grid grid-cols-[6rem_auto] justify-stretch p-4">
                    <x-ui.text>Paciente : </x-ui.text>
                    <x-ui.text class="font-semibold">{{ $note->appointment->user->name }}</x-ui.text>
                </div>

                <x-ui.separator />

                <div class="grid grid-cols-[6rem_auto] justify-stretch p-4">
                    <x-ui.text>Poliza : </x-ui.text>
                    <x-ui.text class="font-semibold">{{ $note->appointment->user->policy->number }}</x-ui.text>
                </div>

                <x-ui.separator />

                <div class="grid grid-cols-[6rem_auto] justify-stretch p-4">
                    <x-ui.text>Doctor : </x-ui.text>
                    <x-ui.text class="font-semibold">{{ $note->appointment->doctor->user->name }}</x-ui.text>
                </div>

                <x-ui.separator />

                <div class="grid grid-cols-[6rem_auto] justify-stretch p-4">
                    <x-ui.text>Fecha : </x-ui.text>
                    <x-ui.text class="font-semibold">{{ $note->appointment->date->format('d/m/Y') }} {{ $note->appointment->time->format('h:i A') }}</x-ui.text>
                </div>
            </a>

            <div class="flex justify-center mt-4">
                <x-ui.button class="w-40 mr-1" wire:click="print" variant="outline" color="indigo" icon="document">
                    Receta digital
                </x-ui.button>
            </div>
        </x-ui.card>
    </div>
</div>
