<div class="max-w-md mx-auto bg-white min-h-screen overflow-hidden font-sans">
    <div class="relative w-full">
        <img src="/img/top.png" alt="Header" class="w-full object-cover">
    </div>

    <div class="grid grid-cols-[2rem_auto] justify-stretch items-center pt-4 pb-4">
        <a href="{{ route('user.home') }}">
            <x-ui.icon name="arrow-left" class="w-5 h-5 cursor-pointer" />
        </a>
        <x-ui.text class="text-2xl">Historial de consultas</x-ui.text>
    </div>

    <div class="relative w-full">
        <x-ui.card size="full">
            <x-ui.heading class="flex justify-center" level="h3" size="sm">
                <x-ui.icon name="calendar" variant="solid" class="self-center" />
                <x-ui.text class="text-lg ml-2">Consulta cancelada!</x-ui.text>
            </x-ui.heading>

            <div class="flex justify-center mt-10 mb-10">
                <x-ui.icon name="check-circle" variant="solid" class="fill-teal-500 size-16"/>
            </div>

            <a href="#" class="flex flex-col bg-[#E3F2FD] rounded-xl shadow-sm hover:shadow-md transition-shadow border border-white/50">
                <div class="grid grid-cols-[6rem_auto] justify-stretch p-4">
                    <x-ui.text>Paciente : </x-ui.text>
                    <x-ui.text class="font-semibold">{{ $appointment->user->name }}</x-ui.text>
                </div>

                <x-ui.separator />

                <div class="grid grid-cols-[6rem_auto] justify-stretch p-4">
                    <x-ui.text>Doctor : </x-ui.text>
                    <x-ui.text class="font-semibold">{{ $appointment->doctor->user->name }}</x-ui.text>
                </div>

                <x-ui.separator />

                <div class="grid grid-cols-[6rem_auto] justify-stretch p-4">
                    <x-ui.text>Fecha : </x-ui.text>
                    <x-ui.text class="font-semibold">{{ $appointment->date->format('d/m/Y') }} {{ $appointment->time->format('h:i A') }}</x-ui.text>
                </div>

                <x-ui.separator />

                <div class="grid grid-cols-[6rem_auto] justify-stretch p-4">
                    <x-ui.text>Ubicación : </x-ui.text>
                    <x-ui.text class="font-semibold">{{ $appointment->doctor->address }}</x-ui.text>
                </div>
            </a>

        </x-ui.card>
    </div>
</div>
